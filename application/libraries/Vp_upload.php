<?php
defined('BASEPATH') OR exit('No direct script access allowed');

/**
 * Vortex Precision - Upload helper wrapping CI3's file upload library.
 *
 * Hardening beyond CI3's extension check:
 *  - uploaded files get random (encrypted) names, so the original
 *    filename never touches the filesystem (no ../ traversal, no
 *    duplicate-name collisions)
 *  - the final file extension must match the caller's whitelist
 *  - content sniffing (finfo) rejects executable payloads (PHP scripts,
 *    shells, binaries) even when they wear an allowed extension
 *  - raster images must actually be images (getimagesize)
 *  - stored original names are sanitized (basename only, control chars
 *    stripped, truncated)
 */
class Vp_upload
{
    /** @var CI_Controller */
    protected $CI;

    /** MIME types that are never acceptable upload content. */
    const BLOCKED_MIMES = [
        'text/x-php',
        'text/php',
        'application/x-php',
        'application/x-httpd-php',
        'application/x-httpd-php-source',
        'text/x-shellscript',
        'application/x-sh',
        'application/x-bsh',
        'application/x-csh',
        'application/x-executable',
        'application/x-msdos-program',
        'application/x-dosexec',
        'application/vnd.microsoft.portable-executable',
        'text/x-python',
        'application/x-perl',
        'application/x-httpd-cgi',
        'application/x-java-applet',
    ];

    public function __construct()
    {
        $this->CI =& get_instance();
        $this->CI->load->library('upload');
        $this->CI->load->helper('security_helper');
    }

    /**
     * Upload a single file with sane defaults.
     *
     * @return array|false  ['path' => '/abs/path', 'url' => '/assets/uploads/...', 'name' => 'orig.jpg', 'size' => N, 'mime' => 'image/jpeg']
     */
    public function handle($field, $folder, $allowed_types = 'jpg|jpeg|png|webp|svg|gif|pdf|doc|docx|xls|xlsx|zip', $max_size_kb = 8192)
    {
        if (!isset($_FILES[$field]) || $_FILES[$field]['error'] === UPLOAD_ERR_NO_FILE) {
            return false;
        }

        // Reject obviously hostile uploads before touching the filesystem.
        if ($_FILES[$field]['error'] !== UPLOAD_ERR_OK) {
            return ['error' => $this->_php_upload_error($_FILES[$field]['error'])];
        }
        if (empty($_FILES[$field]['size']) || (int) $_FILES[$field]['size'] <= 0) {
            return ['error' => 'The uploaded file is empty.'];
        }

        $rel_folder = $this->_sanitize_folder($folder);
        $dest = VP_UPLOAD_PATH . $rel_folder . '/';
        if (!is_dir($dest)) @mkdir($dest, 0755, true);

        $config = [
            'upload_path'   => $dest,
            'allowed_types' => $allowed_types,
            'max_size'      => $max_size_kb,
            'encrypt_name'  => true,   // random filename on disk
            'remove_spaces' => true,
            'file_ext_tolower' => true,
            'detect_mime'   => true,   // finfo; we re-sniff below for extra checks
        ];
        $this->CI->upload->initialize($config);
        if (!$this->CI->upload->do_upload($field)) {
            $err = trim($this->CI->upload->display_errors('', ''));
            log_message('error', 'Vp_upload do_upload failed: ' . $err
                . ' | client_type=' . ($_FILES[$field]['type'] ?? '?')
                . ' | client_name=' . ($_FILES[$field]['name'] ?? '?')
                . ' | tmp=' . ($_FILES[$field]['tmp_name'] ?? '?')
                . ' | ext=' . $this->CI->upload->file_ext
                . ' | detected=' . $this->CI->upload->file_type);
            return ['error' => $err];
        }
        $d = $this->CI->upload->data();
        $abs = $dest . $d['file_name'];

        // 1. Final extension must be in the whitelist (defence in depth).
        $allowed = array_filter(array_map('strtolower', array_map('trim', explode('|', $allowed_types))));
        $ext = strtolower(pathinfo($d['file_name'], PATHINFO_EXTENSION));
        if (!in_array($ext, $allowed, true)) {
            @unlink($abs);
            return ['error' => 'The filetype you are attempting to upload is not allowed.'];
        }

        // 2. Content sniffing: reject executable payloads.
        $mime = $this->_detect_mime($abs);
        if ($mime !== null && in_array($mime, self::BLOCKED_MIMES, true)) {
            @unlink($abs);
            log_message('error', 'Upload blocked by MIME sniffing: ' . $d['orig_name'] . ' -> ' . $mime);
            return ['error' => 'The file content is not allowed.'];
        }

        // 3. Raster images must really be images.
        if (in_array($ext, ['jpg', 'jpeg', 'png', 'webp', 'gif'], true)) {
            $info = @getimagesize($abs);
            if ($info === false) {
                @unlink($abs);
                return ['error' => 'The file is not a valid image.'];
            }
            $valid = [
                'jpg' => [IMAGETYPE_JPEG], 'jpeg' => [IMAGETYPE_JPEG],
                'png' => [IMAGETYPE_PNG], 'webp' => [IMAGETYPE_WEBP],
                'gif' => [IMAGETYPE_GIF],
            ];
            if (!in_array($info[2], $valid[$ext], true)) {
                @unlink($abs);
                return ['error' => 'The image content does not match its extension.'];
            }
        }

        // 4. PDFs must start with the PDF header.
        if ($ext === 'pdf') {
            $head = @file_get_contents($abs, false, null, 0, 5);
            if ($head !== '%PDF-') {
                @unlink($abs);
                return ['error' => 'The file is not a valid PDF.'];
            }
        }

        // 5. Office / archive / CAD formats: validate the actual structure,
        //    not just the extension + MIME type (which libmagic can misreport).
        $structError = $this->_validate_structure($ext, $abs);
        if ($structError !== null) {
            @unlink($abs);
            return ['error' => $structError];
        }

        $rel = $rel_folder . '/' . $d['file_name'];
        return [
            'path'        => $abs,
            'url'         => VP_UPLOAD_URL . $rel,
            'name'        => $this->_sanitize_original_name($d['orig_name']),
            'size'        => $d['file_size'] * 1024,
            'mime'        => $mime !== null ? $mime : $d['file_type'],
            'width'       => $d['image_width'] ?? null,
            'height'      => $d['image_height'] ?? null,
            'folder'      => $rel_folder,
            'filename'    => $d['file_name'],
        ];
    }

    /* ------------------------------------------------------------------ */

    private function _detect_mime($path)
    {
        if (function_exists('finfo_open')) {
            $finfo = @finfo_open(FILEINFO_MIME_TYPE);
            if ($finfo) {
                $mime = @finfo_file($finfo, $path);
                finfo_close($finfo);
                if (is_string($mime) && $mime !== '') return strtolower($mime);
            }
        }
        return null;
    }

    /**
     * Structural validation for the non-image, non-PDF formats the app
     * accepts (Office documents, archives and CAD files). Extension + MIME
     * alone cannot tell a real .docx from a zip archive wearing a .docx
     * name, or a text file wearing a .dwg name. Returns a user-facing error
     * string when the content does not match the extension, or null when it
     * is acceptable.
     */
    private function _validate_structure($ext, $abs)
    {
        $head = $this->_read_head($abs, 16);

        switch ($ext) {
            // OOXML (docx/xlsx) and plain ZIP are all ZIP containers.
            case 'docx':
            case 'xlsx':
            case 'zip':
                if (!$this->_is_zip($head)) {
                    return 'The file is not a valid archive.';
                }
                // A real OOXML package always ships [Content_Types].xml;
                // this rejects a random zip renamed to .docx/.xlsx.
                if ($ext !== 'zip' && !$this->_file_contains($abs, '[Content_Types].xml', 1048576)) {
                    return 'The file is not a valid Office document.';
                }
                break;

            // Legacy binary Office formats are OLE2 compound documents.
            case 'doc':
            case 'xls':
                if (substr($head, 0, 8) !== "\xD0\xCF\x11\xE0\xA1\xB1\x1A\xE1") {
                    return 'The file is not a valid Office document.';
                }
                break;

            // DWG is binary and always carries an "AC10xx" version marker.
            case 'dwg':
                if (substr($head, 0, 4) !== 'AC10') {
                    return 'The file is not a valid DWG drawing.';
                }
                break;

            // DXF is ASCII text made of group-code sections: a group code
            // line "0" followed by the value "SECTION" on the next line.
            case 'dxf':
                $text = $this->_read_head($abs, 8192);
                if (strpos($text, "\x00") !== false
                    || !preg_match('/\b0[ \t]*\r?\n[ \t]*SECTION\b/i', $text)) {
                    return 'The file is not a valid DXF drawing.';
                }
                break;

            // STEP (.step/.stp) files start with the ISO-10303-21 marker.
            case 'step':
            case 'stp':
                $h = preg_replace('/^\xEF\xBB\xBF/', '', $this->_read_head($abs, 1024));
                if (stripos($h, 'ISO-10303-21;') !== 0) {
                    return 'The file is not a valid STEP model.';
                }
                break;

            // IGES (.iges/.igs) is ASCII text (fixed-width records). A full
            // parse is out of scope here; executable/binary payloads are
            // already rejected by the MIME sniff above.
            case 'iges':
            case 'igs':
                if (!$this->_is_text($abs)) {
                    return 'The file is not a valid IGES model.';
                }
                break;
        }

        return null;
    }

    private function _read_head($abs, $len)
    {
        $fh = @fopen($abs, 'rb');
        if (!$fh) return '';
        $data = fread($fh, $len);
        fclose($fh);
        return is_string($data) ? $data : '';
    }

    private function _is_zip($head)
    {
        $magic = substr($head, 0, 4);
        return $magic === "PK\x03\x04" || $magic === "PK\x05\x06" || $magic === "PK\x07\x08";
    }

    /** True when the first 8 KiB contains no NUL bytes (i.e. plain text). */
    private function _is_text($abs)
    {
        $fh = @fopen($abs, 'rb');
        if (!$fh) return false;
        $read = 0;
        $ok = true;
        while (!feof($fh) && $read < 8192) {
            $chunk = fread($fh, 8192);
            if ($chunk === false) { $ok = false; break; }
            if (strpos($chunk, "\x00") !== false) { $ok = false; break; }
            $read += strlen($chunk);
        }
        fclose($fh);
        return $ok;
    }

    /** Scan the first $maxBytes of a file for a byte sequence. */
    private function _file_contains($abs, $needle, $maxBytes, $caseSensitive = true)
    {
        $fh = @fopen($abs, 'rb');
        if (!$fh) return false;
        $read = 0;
        $found = false;
        while (!feof($fh) && $read < $maxBytes) {
            $chunk = fread($fh, 8192);
            if ($chunk === false) break;
            $chunk = substr($chunk, 0, max(0, $maxBytes - $read));
            $pos = $caseSensitive ? strpos($chunk, $needle) : stripos($chunk, $needle);
            if ($pos !== false) { $found = true; break; }
            $read += strlen($chunk);
        }
        fclose($fh);
        return $found;
    }

    private function _php_upload_error($code)
    {
        switch ($code) {
            case UPLOAD_ERR_INI_SIZE:
            case UPLOAD_ERR_FORM_SIZE:
                return 'The uploaded file exceeds the maximum allowed size.';
            case UPLOAD_ERR_PARTIAL:
                return 'The file was only partially uploaded. Please try again.';
            case UPLOAD_ERR_NO_TMP_DIR:
                return 'Server upload directory is missing.';
            case UPLOAD_ERR_CANT_WRITE:
                return 'Server could not write the upload.';
            case UPLOAD_ERR_EXTENSION:
                return 'The upload was blocked by a PHP extension.';
            default:
                return 'Upload failed (error code ' . (int) $code . ').';
        }
    }

    /** Folder must be a single relative path segment: [a-z0-9_-]+ */
    private function _sanitize_folder($folder)
    {
        $folder = trim((string) $folder, "/ \t\n\r\0\x0B");
        $folder = basename(str_replace('\\', '/', $folder));
        if ($folder === '' || $folder === '.' || $folder === '..' || !preg_match('/^[a-zA-Z0-9._-]{1,64}$/', $folder)) {
            return 'general';
        }
        return $folder;
    }

    /** Stored for display only - strip paths, control chars and length. */
    private function _sanitize_original_name($name)
    {
        $name = basename(str_replace('\\', '/', (string) $name));
        $name = preg_replace('/[\x00-\x1f\x7f]/u', '', $name);
        return mb_substr($name, 0, 200) ?: 'file';
    }

    /**
     * Best-effort GD resize to max width, keeping aspect.
     * Returns true on success, false if no GD or file isn't a JPEG/PNG/WEBP.
     */
    public function resize_image($abs_path, $max_width = 1600)
    {
        if (!function_exists('imagecreatetruecolor')) return false;
        if (!is_file($abs_path)) return false;
        $info = getimagesize($abs_path);
        if (!$info) return false;
        [$w, $h, $type] = $info;
        if ($w <= $max_width) return true;
        $ratio = $max_width / $w;
        $new_w = $max_width;
        $new_h = (int) round($h * $ratio);
        $src = null;
        switch ($type) {
            case IMAGETYPE_JPEG: $src = @imagecreatefromjpeg($abs_path); break;
            case IMAGETYPE_PNG:  $src = @imagecreatefrompng($abs_path);  break;
            case IMAGETYPE_WEBP: if (function_exists('imagecreatefromwebp')) $src = @imagecreatefromwebp($abs_path); break;
            case IMAGETYPE_GIF:  $src = @imagecreatefromgif($abs_path);  break;
            default: return false;
        }
        if (!$src) return false;
        $dst = imagecreatetruecolor($new_w, $new_h);
        if ($type === IMAGETYPE_PNG || $type === IMAGETYPE_WEBP) {
            imagealphablending($dst, false);
            imagesavealpha($dst, true);
            $transparent = imagecolorallocatealpha($dst, 0, 0, 0, 127);
            imagefilledrectangle($dst, 0, 0, $new_w, $new_h, $transparent);
        }
        imagecopyresampled($dst, $src, 0, 0, 0, 0, $new_w, $new_h, $w, $h);
        switch ($type) {
            case IMAGETYPE_JPEG: imagejpeg($dst, $abs_path, 88); break;
            case IMAGETYPE_PNG:  imagepng($dst, $abs_path, 6);   break;
            case IMAGETYPE_WEBP: if (function_exists('imagewebp')) imagewebp($dst, $abs_path, 88); break;
            case IMAGETYPE_GIF:  imagegif($dst, $abs_path);      break;
        }
        imagedestroy($src);
        imagedestroy($dst);
        return true;
    }
}

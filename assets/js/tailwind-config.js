// Vortex Precision - Tailwind CDN configuration.
// Must be loaded BEFORE https://cdn.tailwindcss.com. Lives in an external
// file because the production CSP intentionally blocks inline scripts.
window.tailwind = window.tailwind || {};
window.tailwind.config = {
    theme: {
        extend: {
            fontFamily: { sans: ['Inter', 'system-ui', 'sans-serif'] },
            colors: {
                brand: {
                    50:'#eef5ff',100:'#d9e8ff',200:'#bcd6ff',300:'#8ebbff',
                    400:'#5a99ff',500:'#2f78ff',600:'#1659e6',700:'#0f44b8',
                    800:'#0e3893',900:'#0e3077'
                },
                ink: { 900:'#0b1424', 800:'#101b2e', 700:'#1b2740' }
            }
        }
    }
};

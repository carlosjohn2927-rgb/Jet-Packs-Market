// JetPacks Market - Tailwind CDN configuration.
// Must be loaded BEFORE https://cdn.tailwindcss.com. Lives in an external
// file because the production CSP intentionally blocks inline scripts.
window.tailwind = window.tailwind || {};
window.tailwind.config = {
    theme: {
        extend: {
            fontFamily: { sans: ['Inter', 'system-ui', 'sans-serif'] },
            colors: {
                brand: {
                    50:'#eef6ff',100:'#d9ecff',200:'#b6d9ff',300:'#8cc0ff',
                    400:'#5b9fff',500:'#2f7df5',600:'#135fd4',700:'#0f4aa8',
                    800:'#0d3d87',900:'#0b2f68'
                },
                amber: {
                    50:'#fffbeb',100:'#fef3c7',400:'#fbbf24',500:'#f59e0b',
                    600:'#d97706',700:'#b45309'
                },
                sky: {
                    50:'#f0f9ff',100:'#e0f2fe',400:'#38bdf8',500:'#0ea5e9',
                    600:'#0284c7',700:'#0369a1'
                },
                ink: { 900:'#0a1628', 800:'#101f38', 700:'#1c2e4d' }
            }
        }
    }
};

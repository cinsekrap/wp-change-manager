<link rel="preconnect" href="https://fonts.googleapis.com">
<link rel="preconnect" href="https://fonts.gstatic.com" crossorigin>
<link href="https://fonts.googleapis.com/css2?family=Nunito+Sans:ital,wght@0,200;0,300;0,400;0,600;0,700;1,300&display=swap" rel="stylesheet">
<script src="https://cdn.tailwindcss.com"></script>
<script>
tailwind.config = {
    theme: {
        extend: {
            colors: {
                'hcrg': {
                    'burgundy': '#B52159',
                    'charcoal': '#3C3C3B',
                    'grey': {
                        100: '#F0F0EF',
                        200: '#D2D2D1',
                        300: '#A0A09F',
                        400: '#6E6E6D',
                        500: '#3C3C3B',
                    },
                    'white': '#FFFFFF',
                },
                'status': {
                    'success': '#3CB764',
                    'error': '#CC0000',
                    'warning': '#F39204',
                    'gold': '#F4991A',
                },
            },
            fontFamily: {
                'sans': ['"Nunito Sans"', 'Verdana', 'Geneva', 'sans-serif'],
            },
            borderRadius: {
                'pill': '3rem',
                'medium': '1rem',
            },
        },
    },
}
</script>
<style>
    body { font-weight: 300; }
    h1, h2, h3, h4, h5, h6 { font-weight: 700; color: #B52159; }
    a, button, select, input, textarea, [class*="hover:"] { transition: all 200ms ease; }
</style>

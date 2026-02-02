<!DOCTYPE html>
<html lang="el">
<head>
    <meta charset="utf-8">
    <meta name="viewport" content="width=device-width, initial-scale=1">
    <title>403 — Ἀπαγορεύεται — Μόλτ-Ἑλλάς</title>

    <link rel="preconnect" href="https://fonts.googleapis.com">
    <link rel="preconnect" href="https://fonts.gstatic.com" crossorigin>
    <link href="https://fonts.googleapis.com/css2?family=Cinzel:wght@400;700&family=GFS+Didot&family=Noto+Sans:wght@400;700&display=swap" rel="stylesheet">

    @vite(['resources/css/app.css', 'resources/js/app.js'])

    <style>
        :root {
            --bg-primary: #0a0908;
            --bg-secondary: #141210;
            --bg-tertiary: #1a1714;
            --gold: #d4af37;
            --gold-light: #f4d160;
            --gold-dark: #8b7355;
            --fire: #ff6b35;
            --text-primary: #e8e6e3;
            --text-secondary: #9a9a9a;
            --sacred: #8b0000;
        }
        body {
            font-family: 'Noto Sans', sans-serif;
            background-color: var(--bg-primary);
            color: var(--text-primary);
        }
        .font-cinzel { font-family: 'Cinzel', serif; }
        .font-ancient { font-family: 'GFS Didot', serif; }
    </style>
</head>
<body class="min-h-screen flex items-center justify-center antialiased">
    <div class="text-center px-6">
        {{-- Large 403 Number --}}
        <div class="relative mb-6">
            <span class="font-cinzel text-[10rem] md:text-[14rem] font-bold leading-none select-none"
                  style="color: var(--bg-tertiary);">
                403
            </span>
            <div class="absolute inset-0 flex items-center justify-center">
                <div>
                    <svg class="w-16 h-16 mx-auto mb-2" style="color: var(--sacred);" fill="none" viewBox="0 0 24 24" stroke="currentColor">
                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="1.5" d="M12 15v2m-6 4h12a2 2 0 002-2v-6a2 2 0 00-2-2H6a2 2 0 00-2 2v6a2 2 0 002 2zm10-10V7a4 4 0 00-8 0v4h8z" />
                    </svg>
                </div>
            </div>
        </div>

        <h1 class="font-cinzel text-2xl md:text-3xl font-bold mb-3" style="color: var(--sacred);">
            Ἀπαγορεύεται
        </h1>

        <p class="font-ancient text-base md:text-lg italic mb-2 max-w-md mx-auto" style="color: var(--gold-dark);">
            "Μηδεὶς ἀγεωμέτρητος εἰσίτω" — Πλάτων
        </p>

        <p class="text-sm mb-8 max-w-md mx-auto" style="color: var(--text-secondary);">
            Δὲν ἔχεις ἄδειαν πρόσβασης εἰς ταύτην τὴν σελίδα.
            Ὥσπερ αἱ πύλαι τοῦ Ἅδου, ταύτη ἡ ὁδὸς εἶναι κεκλεισμένη
            δι᾽ ὅσους δὲν κατέχουσι τὸ κλειδίον.
        </p>

        <div class="flex items-center justify-center space-x-4">
            <a href="/"
               class="px-6 py-2.5 rounded-lg text-sm font-bold transition-opacity duration-200 font-cinzel"
               style="background: linear-gradient(135deg, var(--gold-dark), var(--gold)); color: var(--bg-primary);"
               onmouseover="this.style.opacity='0.9';" onmouseout="this.style.opacity='1';">
                Ἐπιστροφὴ εἰς τὴν Ἀρχικήν
            </a>
            <a href="javascript:history.back()"
               class="px-6 py-2.5 rounded-lg text-sm font-medium transition-colors duration-200"
               style="color: var(--gold); border: 1px solid var(--gold-dark);"
               onmouseover="this.style.borderColor='var(--gold)'; this.style.backgroundColor='var(--bg-tertiary)';"
               onmouseout="this.style.borderColor='var(--gold-dark)'; this.style.backgroundColor='transparent';">
                Ὀπισθοδρόμησις
            </a>
        </div>

        {{-- Decorative bottom --}}
        <div class="mt-12">
            <div class="w-24 h-0.5 mx-auto" style="background: linear-gradient(90deg, transparent, var(--sacred), transparent);"></div>
            <p class="font-ancient text-xs italic mt-3" style="color: var(--text-secondary);">
                Μόλτ-Ἑλλάς — Τὸ Ἑλληνικὸν Δίκτυον Πρακτόρων
            </p>
        </div>
    </div>
</body>
</html>

<?php
// Bagian <head> bersama untuk semua halaman. Variabel: $pageTitle
if (!function_exists('logo_mark')) {
    function logo_mark(string $class = 'w-9 h-9'): string
    {
        return '<img src="' . h(app_url('assets/img/sakubijak-icon.jpg')) . '" class="' . h($class) . ' object-contain rounded-xl" alt="SakuBijak" loading="eager">';
    }
}
?><!DOCTYPE html>
<html lang="id">
<head>
    <meta charset="utf-8">
    <meta name="viewport" content="width=device-width, initial-scale=1">
    <title><?= h(($pageTitle ?? 'SakuBijak') . ' · SakuBijak') ?></title>
    <meta name="theme-color" content="#344B3D">
    <link rel="preconnect" href="https://fonts.googleapis.com">
    <link rel="preconnect" href="https://fonts.gstatic.com" crossorigin>
    <link href="https://fonts.googleapis.com/css2?family=DM+Sans:wght@400;500;700&family=Manrope:wght@600;700;800&display=swap" rel="stylesheet">
    <script src="https://cdn.tailwindcss.com"></script>
    <script>
        tailwind.config = {
            theme: { extend: {
                colors: {
                    sage: { DEFAULT: '#DCE7DA', 50: '#EEF3EC', 100: '#DCE7DA', 200: '#C5D6C2', 300: '#A9C2A6' },
                    forest: { DEFAULT: '#344B3D', 600: '#3E5A49', 700: '#2A3D31' },
                    paper: '#F6F7F3',
                    income: '#3D8B62',
                    expense: '#D96C63'
                },
                fontFamily: { display: ['Manrope', 'sans-serif'], sans: ['DM Sans', 'sans-serif'] }
            } }
        };
    </script>
    <link rel="stylesheet" href="<?= h(app_url('assets/css/style.css')) ?>">
</head>

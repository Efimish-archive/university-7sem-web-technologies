<?php require_once __DIR__ . '/db.php'; ?>
<!doctype html>
<html lang="ru">

<head>
  <meta charset="utf-8">
  <meta name="viewport" content="width=device-width, initial-scale=1">
  <meta name="description" content="Учебное серверное приложение на PHP и MySQL">
  <title><?= e($pageTitle ?? 'Серверное приложение') ?></title>
  <style>
    :root {
      font: 16px/1.6 system-ui, sans-serif;
      color: #202936;
      background: #f5f7fb
    }

    * {
      box-sizing: border-box
    }

    body {
      max-width: 1000px;
      margin: 0 auto;
      padding: 1rem
    }

    header,
    footer,
    article,
    .card {
      padding: 1rem 1.25rem;
      background: white;
      border: 1px solid #dce2eb;
      border-radius: 12px
    }

    header h1 {
      margin: .2rem 0
    }

    nav ul {
      display: flex;
      gap: .6rem;
      flex-wrap: wrap;
      list-style: none;
      padding: 0
    }

    nav a,
    .button {
      display: inline-block;
      padding: .45rem .75rem;
      border-radius: 7px;
      background: #eaf0ff;
      color: #173e91;
      text-decoration: none;
      border: 0;
      cursor: pointer;
      font: inherit
    }

    main {
      min-height: 50vh
    }

    input,
    textarea,
    button {
      font: inherit;
      padding: .5rem;
      border: 1px solid #bbc5d5;
      border-radius: 6px
    }

    label {
      display: block;
      margin: .6rem 0
    }

    article,
    .card {
      margin: 1rem 0
    }

    img {
      max-width: 100%;
      height: auto
    }

    .message {
      padding: .7rem;
      background: #e7f7eb;
      border-radius: 6px
    }

    .error {
      padding: .7rem;
      background: #fff0f0;
      color: #900;
      border-radius: 6px
    }

    .grid {
      display: grid;
      grid-template-columns: repeat(auto-fit, minmax(220px, 1fr));
      gap: 1rem
    }

    .thumb {
      width: 100%;
      height: 220px;
      object-fit: cover
    }

    table {
      border-collapse: collapse;
      width: 100%;
      background: white
    }

    td,
    th {
      border: 1px solid #dce2eb;
      padding: .5rem;
      text-align: left
    }

    footer {
      margin-top: 2rem
    }

    @media(max-width:600px) {
      body {
        padding: .6rem
      }

      nav ul {
        gap: .35rem
      }
    }
  </style>
</head>

<body>
  <header>
    <h1>Сайт Ефима и Артемия</h1>
    <p>Учебный проект · серверная часть</p>
  </header>
  <nav aria-label="Основное меню"><?= getMenu() ?></nav>
  <main>

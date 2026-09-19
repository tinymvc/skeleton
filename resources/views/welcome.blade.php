<!DOCTYPE html>
<html lang="en">

<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>TinyMVC</title>
    <style>
        * {
            margin: 0;
            padding: 0;
            box-sizing: border-box;
        }

        body {
            font-family: -apple-system, BlinkMacSystemFont, 'Segoe UI', Roboto, sans-serif;
            background: #fff;
            color: #1a1a1a;
            min-height: 100vh;
            display: flex;
            align-items: center;
            justify-content: center;
        }

        .wrap {
            text-align: center;
            padding: 2rem;
            max-width: 520px;
        }

        .logo {
            width: 60px;
            height: 60px;
        }

        .branding {
            position: relative;
            width: max-content;
            margin: auto;
            font-size: 29px;
            margin-bottom: 20px;
        }

        .branding small {
            position: absolute;
            bottom: -3px;
            right: 1px;
            letter-spacing: 1px;
            text-transform: uppercase;
            text-align: right;
            margin-top: -2px;
            font-size: 7px;
            font-weight: 500;
            display: block;
            opacity: 0.85;
        }

        h1 {
            font-size: 2rem;
            font-weight: 700;
            letter-spacing: -0.5px;
            margin-bottom: 0.25rem;
        }

        p {
            color: #666;
            line-height: 1.7;
            font-size: 0.95rem;
            margin-bottom: 2.5rem;
        }

        .links {
            display: flex;
            gap: 10px;
            justify-content: center;
            margin-bottom: 2.5rem;
        }

        .links a {
            display: inline-block;
            padding: 10px 22px;
            border-radius: 8px;
            text-decoration: none;
            font-size: 0.85rem;
            font-weight: 600;
            transition: opacity 0.2s;
        }

        .links a:hover {
            opacity: 0.85;
        }

        .links .docs {
            background: #1a1a1a;
            color: #fff;
        }

        .links .gh {
            background: #f4f4f5;
            color: #1a1a1a;
        }

        .sep {
            width: 32px;
            height: 1px;
            background: #e5e5e5;
            margin: 0 auto 1.5rem;
        }

        .bottom a {
            color: #999;
            text-decoration: none;
            font-size: 0.8rem;
            margin: 0 12px;
            transition: color 0.2s;
        }

        .bottom a:hover {
            color: #1a1a1a;
        }

        @media (max-width: 480px) {
            h1 {
                font-size: 1.6rem;
            }

            .links {
                flex-direction: column;
                align-items: center;
            }

            .links a {
                width: 100%;
                text-align: center;
            }
        }
    </style>
</head>

<body>
    <div class="wrap">
        <img class="logo" src="https://tinymvc.github.io/flame.svg" alt="TinyMVC Logo" />
        <h2 class="branding">
            spark
            <small>BY TINYMVC</small>
        </h2>
        <p>A lightweight MVC framework for PHP artisans.<br>Simple tools, clean code, real projects.</p>
        <div class="links">
            <a href="https://tinymvc.github.io" class="docs" target="_blank">Documentation</a>
            <a href="https://github.com/tinymvc/tinycore/tree/main/src" class="gh" target="_blank">GitHub</a>
        </div>
        <div class="sep"></div>
        <div class="bottom">
            <a href="https://github.com/tinymvc/tinycore/stargazers" target="_blank">Star</a>
            <a href="https://github.com/tinymvc/tinycore/issues" target="_blank">Issues</a>
            <a href="https://github.com/tinymvc/tinycore/releases" target="_blank">Releases</a>
        </div>
    </div>
</body>

</html>

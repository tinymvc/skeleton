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

        .mark {
            display: inline-block;
            width: 48px;
            height: 48px;
            background: #1a1a1a;
            border-radius: 10px;
            margin-bottom: 2rem;
            position: relative;
        }

        .mark::after {
            content: '';
            position: absolute;
            top: 50%;
            left: 50%;
            width: 18px;
            height: 18px;
            border: 2.5px solid #fff;
            border-radius: 3px;
            transform: translate(-50%, -50%) rotate(45deg);
        }

        h1 {
            font-size: 2rem;
            font-weight: 700;
            letter-spacing: -0.5px;
            margin-bottom: 0.25rem;
        }

        .ver {
            font-size: 0.8rem;
            color: #999;
            font-weight: 500;
            margin-bottom: 1.5rem;
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
        <div class="mark"></div>
        <h1>TinyMVC</h1>
        <div class="ver">&mdash;Spark v2.3.x</div>
        <p>A lightweight MVC framework for PHP.<br>Simple tools, clean code, real projects.</p>
        <div class="links">
            <a href="https://tinymvc.github.io" class="docs" target="_blank">Documentation</a>
            <a href="https://github.com/tinymvc/tinymvc" class="gh" target="_blank">GitHub</a>
        </div>
        <div class="sep"></div>
        <div class="bottom">
            <a href="https://github.com/tinymvc/tinymvc/stargazers" target="_blank">Star</a>
            <a href="https://github.com/tinymvc/tinymvc/issues" target="_blank">Issues</a>
            <a href="https://github.com/tinymvc/tinymvc/releases" target="_blank">Releases</a>
        </div>
    </div>
</body>

</html>

<!DOCTYPE html>
<html lang="en">

<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>TinyMVC Framework</title>
    <style>
        :root {
            --primary: #4F46E5;
            --primary-hover: #4338CA;
        }

        * {
            margin: 0;
            padding: 0;
            box-sizing: border-box;
            font-family: -apple-system, BlinkMacSystemFont, 'Segoe UI', Roboto, Oxygen, Ubuntu, Cantarell, sans-serif;
        }

        body {
            background: #f8fafc;
            height: 100vh;
            display: flex;
            align-items: center;
            justify-content: center;
            flex-direction: column;
            line-height: 1.6;
        }

        .card {
            background: white;
            padding: 3rem;
            border-radius: 1rem;
            box-shadow: 0 4px 6px -1px rgb(0 0 0 / 0.1);
            text-align: center;
            max-width: 600px;
            margin: 2rem;
        }

        h1 {
            color: var(--primary);
            font-size: 2.5rem;
            margin-bottom: 1rem;
        }

        .version {
            color: #64748b;
            font-weight: 500;
            margin-bottom: 2rem;
        }

        .links {
            display: flex;
            gap: 1rem;
            justify-content: center;
            margin-top: 2rem;
        }

        .btn {
            padding: 0.75rem 1.5rem;
            border-radius: 0.5rem;
            text-decoration: none;
            font-weight: 500;
            transition: all 0.2s;
        }

        .btn-primary {
            background: var(--primary);
            color: white;
        }

        .btn-primary:hover {
            background: var(--primary-hover);
        }

        .btn-gh {
            background: #1F2937;
            color: white;
        }

        .btn-gh:hover {
            background: #111827;
        }
    </style>
</head>

<body>
    <div class="card">
        <h1>TinyMVC Framework</h1>
        <p class="version">Core v2.1.x</p>

        <p>A minimalist MVC framework for PHP artisans.<br>
            Simple, elegant, and powerful foundation for your next project.</p>

        <div class="links">
            <a href="https://tinymvc.github.io" class="btn btn-primary" target="_blank">
                Documentation
            </a>
            <a href="https://github.com/tinymvc/tinymvc" class="btn btn-gh" target="_blank">
                GitHub Repository
            </a>
        </div>

        <div class="links" style="margin-top: 1.5rem;">
            <a href="https://github.com/tinymvc/tinymvc/stargazers" class="btn btn-gh" target="_blank">
                ⭐ Star on GitHub
            </a>
            <a href="https://github.com/tinymvc/tinymvc/issues" class="btn btn-gh" target="_blank">
                🐞 Report an Issue
            </a>
        </div>
    </div>
</body>

</html>

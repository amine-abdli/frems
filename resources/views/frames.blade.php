
<!DOCTYPE html>
<html lang="fr">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>FrameSnap Studio</title>

    <style>
        * { box-sizing: border-box; }

        body {
            margin: 0;
            padding: 40px 16px;
            min-height: 100vh;
            background: #0b1020;
            color: #f8fafc;
            font-family: Arial, sans-serif;
        }

        .container {
            max-width: 720px;
            margin: auto;
        }

        header {
            text-align: center;
            margin-bottom: 30px;
        }

        .logo { font-size: 42px; }

        h1 { font-size: 34px; }

        h1 span { color: #818cf8; }

        .subtitle {
            color: #94a3b8;
            line-height: 1.6;
        }

        .card {
            padding: 28px;
            background: #111827;
            border: 1px solid #293449;
            border-radius: 18px;
        }

        .field { margin-bottom: 24px; }

        label {
            display: block;
            margin-bottom: 10px;
            font-weight: bold;
        }

        input {
            width: 100%;
            padding: 14px;
            border: 1px solid #374151;
            border-radius: 10px;
            background: #0b1020;
            color: white;
            font-size: 15px;
        }

        input:focus {
            outline: 2px solid #818cf8;
        }

        .hint {
            color: #94a3b8;
            font-size: 13px;
            line-height: 1.6;
        }

        .presets {
            display: flex;
            flex-wrap: wrap;
            gap: 8px;
            margin-top: 12px;
        }

        .preset {
            padding: 9px 13px;
            border: 1px solid #374151;
            border-radius: 8px;
            background: #1e293b;
            color: #c7d2fe;
            cursor: pointer;
        }

        .preset:hover { background: #3730a3; }

        .primary {
            width: 100%;
            padding: 15px;
            border: 0;
            border-radius: 10px;
            background: #6366f1;
            color: white;
            font-size: 16px;
            font-weight: bold;
            cursor: pointer;
        }

        .primary:hover { background: #4f46e5; }

        .primary:disabled {
            opacity: 0.6;
            cursor: wait;
        }

        .message {
            padding: 14px;
            margin-bottom: 20px;
            border-radius: 10px;
            overflow-wrap: anywhere;
            line-height: 1.6;
        }

        .success { background: #14532d; color: #bbf7d0; }
        .error { background: #7f1d1d; color: #fecaca; }

        .footer {
            text-align: center;
            color: #64748b;
            margin-top: 24px;
            font-size: 12px;
        }

        @media (max-width: 500px) {
            h1 { font-size: 27px; }
            .card { padding: 20px; }
        }
    </style>
</head>

<body>
<div class="container">

    <header>
        <div class="logo">🎞️</div>
        <h1>FrameSnap <span>Studio</span></h1>
        <p class="subtitle">
            Collez un lien YouTube et extrayez automatiquement
            les images de la vidéo pour Photoshop.
        </p>
    </header>

    <main class="card">

        @if (session('success'))
            <div class="message success">
                {{ session('success') }}
            </div>
        @endif

        @if ($errors->any())
            <div class="message error">
                <strong>Une erreur est survenue :</strong>
                <ul>
                    @foreach ($errors->all() as $error)
                        <li>{{ $error }}</li>
                    @endforeach
                </ul>
            </div>
        @endif

        <form
            id="extractForm"
            action="{{ route('frames.extract') }}"
            method="POST"
        >
            @csrf

            <div class="field">
                <label for="youtube_url">1. Lien de la vidéo YouTube</label>

                <input
                    type="url"
                    id="youtube_url"
                    name="youtube_url"
                    placeholder="https://www.youtube.com/watch?v=..."
                    value="{{ old('youtube_url') }}"
                    required
                >

                <p class="hint">
                    Collez le lien d'une vidéo que vous êtes autorisé
                    à télécharger ou à traiter.
                </p>
            </div>

            <div class="field">
                <label for="interval">
                    2. Intervalle entre les images (secondes)
                </label>

                <input
                    type="number"
                    id="interval"
                    name="interval"
                    min="0.1"
                    max="60"
                    step="0.1"
                    value="{{ old('interval', '0.1') }}"
                    required
                >

                <div class="presets">
                    <button type="button" class="preset" data-value="0.1">
                        0,1 s
                    </button>
                    <button type="button" class="preset" data-value="0.5">
                        0,5 s
                    </button>
                    <button type="button" class="preset" data-value="1">
                        1 s
                    </button>
                    <button type="button" class="preset" data-value="2">
                        2 s
                    </button>
                </div>

                <p class="hint" id="estimate">
                    0,1 seconde correspond à environ 10 images par seconde.
                </p>
            </div>

            <button type="submit" class="primary" id="submitButton">
                Extraire les captures
            </button>
        </form>
    </main>

    <footer class="footer">
        FrameSnap Studio · YouTube + FFmpeg
    </footer>
</div>

<script>
    const intervalInput = document.getElementById('interval');
    const estimate = document.getElementById('estimate');
    const form = document.getElementById('extractForm');
    const submitButton = document.getElementById('submitButton');

    document.querySelectorAll('.preset').forEach(button => {
        button.addEventListener('click', () => {
            intervalInput.value = button.dataset.value;
            updateEstimate();
        });
    });

    function updateEstimate() {
        const interval = Number(intervalInput.value);

        if (!interval || interval < 0.1 || interval > 60) {
            estimate.textContent =
                'Choisissez un intervalle entre 0,1 et 60 secondes.';
            return;
        }

        estimate.textContent =
            `Environ ${(1 / interval).toLocaleString('fr-FR', {
                maximumFractionDigits: 2
            })} image(s) par seconde.`;
    }

    intervalInput.addEventListener('input', updateEstimate);

    form.addEventListener('submit', () => {
        submitButton.disabled = true;
        submitButton.textContent = 'Téléchargement et extraction en cours...';
    });

    updateEstimate();
</script>
</body>
</html>

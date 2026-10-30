<?php

namespace App\Http\Controllers;

use Illuminate\Http\Request;
use Illuminate\Support\Facades\File;
use Illuminate\Support\Str;
use Symfony\Component\Process\Process;

class VideoFrameController extends Controller
{
    public function index()
    {
        return view('frames');
    }

    public function extract(Request $request)
    {
        set_time_limit(0);

        $request->validate([
            'youtube_url' => [
                'required',
                'url',
                'regex:/^https?:\/\/(www\.|m\.)?(youtube\.com|youtu\.be)\//i',
            ],
            'interval' => ['required', 'numeric', 'min:0.1', 'max:60'],
        ]);

        $url = $request->input('youtube_url');
        $interval = (float) $request->input('interval');

        $basePath = env('LOCALAPPDATA') ?: 'C:\\Users\\amine\\AppData\\Local';

        $ytDlp = $basePath
            . '\\Microsoft\\WinGet\\Packages\\yt-dlp.yt-dlp_Microsoft.Winget.Source_8wekyb3d8bbwe\\yt-dlp.exe';

        $ffmpeg = $basePath
            . '\\Microsoft\\WinGet\\Packages\\Gyan.FFmpeg_Microsoft.Winget.Source_8wekyb3d8bbwe\\ffmpeg-9.0.2-full_build\\bin\\ffmpeg.exe';

        if (!File::exists($ytDlp)) {
            return back()->withErrors(['youtube_url' => 'yt-dlp introuvable.'])->withInput();
        }

        if (!File::exists($ffmpeg)) {
            return back()->withErrors(['youtube_url' => 'FFmpeg introuvable.'])->withInput();
        }

        $id = now()->format('Ymd_His') . '_' . Str::random(5);

        $tempFolder = storage_path('app/youtube_temp/' . $id);
        $outputFolder = 'C:\\Users\\amine\\Desktop\\scren photo en yotube\\pictures\\' . $id;

        File::ensureDirectoryExists($tempFolder);
        File::ensureDirectoryExists($outputFolder);

        $tempPath = storage_path('app/tmp');
        File::ensureDirectoryExists($tempPath);

        $processEnv = ['TEMP' => $tempPath, 'TMP' => $tempPath];

        // 1) Téléchargement (vidéo + audio fusionnés en mp4)
        $download = new Process([
            $ytDlp,
            '--ignore-config',
            '--no-playlist',
            '--no-warnings',
            '--ffmpeg-location', dirname($ffmpeg),
            '-f', 'bv*[ext=mp4]+ba[ext=m4a]/b[ext=mp4]/bv*+ba/b',
            '--merge-output-format', 'mp4',
            '-o', $tempFolder . DIRECTORY_SEPARATOR . 'video.%(ext)s',
            $url,
        ]);
        $download->setEnv($processEnv);
        $download->setTimeout(3600);
        $download->run();

        if (!$download->isSuccessful()) {
            $error = trim($download->getErrorOutput() . "\n" . $download->getOutput());
            File::deleteDirectory($tempFolder);
            File::deleteDirectory($outputFolder);

            return back()->withErrors([
                'youtube_url' => 'Échec du téléchargement YouTube : ' . ($error ?: 'Erreur inconnue.'),
            ])->withInput();
        }

        // 2) Choix du bon fichier : mp4 d'abord, sinon le plus gros fichier vidéo
        $files = File::files($tempFolder);

        $videoFiles = array_values(array_filter($files, function ($file) {
            return strtolower($file->getExtension()) === 'mp4';
        }));

        if (empty($videoFiles)) {
            $videoFiles = array_values(array_filter($files, function ($file) {
                return !in_array(strtolower($file->getExtension()), [
                    'part', 'json', 'jpg', 'webp', 'vtt', 'srt', 'm4a', 'ytdl',
                ], true);
            }));
        }

        usort($videoFiles, fn($a, $b) => $b->getSize() <=> $a->getSize());

        if (empty($videoFiles) || $videoFiles[0]->getSize() === 0) {
            File::deleteDirectory($tempFolder);
            File::deleteDirectory($outputFolder);

            return back()->withErrors([
                'youtube_url' => 'La vidéo téléchargée est introuvable ou vide.',
            ])->withInput();
        }

        $videoPath = str_replace('\\', '/', $videoFiles[0]->getPathname());
        $outputFolder = str_replace('\\', '/', $outputFolder);
        $pattern = $outputFolder . '/image_%06d.jpg';

        // fps décimal avec un point (ex: 0.5), jamais une virgule
        $fps = rtrim(rtrim(number_format(1 / $interval, 6, '.', ''), '0'), '.');

        // 3) Extraction des images
        $extract = new Process([
            $ffmpeg,
            '-hide_banner',
            '-loglevel', 'error',
            '-i', $videoPath,
            '-map', '0:v:0',
            '-an',
            '-vf', 'fps=' . $fps,
            '-q:v', '2',
            '-start_number', '1',
            '-y',
            $pattern,
        ]);
        $extract->setTimeout(null);
        $extract->run();

        File::deleteDirectory($tempFolder);

        if (!$extract->isSuccessful()) {
            $error = trim($extract->getErrorOutput() . "\n" . $extract->getOutput());
            File::deleteDirectory($outputFolder);

            return back()->withErrors([
                'youtube_url' => 'Échec de l’extraction FFmpeg : ' . ($error ?: 'Erreur inconnue.'),
            ])->withInput();
        }

        $count = count(glob($outputFolder . '/image_*.jpg') ?: []);

        if ($count === 0) {
            File::deleteDirectory($outputFolder);

            return back()->withErrors([
                'youtube_url' => 'Aucune image extraite. Vérifiez la vidéo et l’intervalle.',
            ])->withInput();
        }

        return back()->with(
            'success',
            $count . ' images extraites avec succès. Dossier : ' . $outputFolder
        );
    }
}
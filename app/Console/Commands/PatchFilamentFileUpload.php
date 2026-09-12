<?php

namespace App\Console\Commands;

use Illuminate\Console\Command;

/**
 * Filament's compiled file-upload widget fetches an already-stored file's
 * bytes in the browser (to build its preview / determine its size) with no
 * timeout and no error handling: `fetch(source).then(r => r.blob())`. If
 * that request never resolves — the CDN is unreachable from the admin's
 * browser, a network blip, anything — the widget is stuck showing "Waiting
 * for size" / a spinner forever, with no way to remove the file.
 *
 * This patches the compiled asset (vendor dist, which composer install
 * rebuilds from scratch, and the public copy Filament serves) to add a
 * 15s timeout and route failures through FilePond's error callback instead,
 * so a broken file surfaces a "tap to retry" / remove state instead of an
 * unrecoverable hang. Run automatically after `composer install` (see
 * composer.json's post-autoload-dump), right after `filament:upgrade`
 * re-copies the vendor asset over the public one.
 */
class PatchFilamentFileUpload extends Command
{
    protected $signature = 'app:patch-filament-file-upload';

    protected $description = 'Patch Filament\'s file-upload widget so an unreachable file errors out instead of loading forever';

    private const SEARCH = 'load:async(N,W)=>{let Z=await(await fetch(N,{cache:"no-store"})).blob();W(Z)}';

    private const REPLACE = 'load:async(N,W,X)=>{let Zc=new AbortController(),Zto=setTimeout(()=>Zc.abort(),15000);try{let Zr=await fetch(N,{cache:"no-store",signal:Zc.signal});clearTimeout(Zto);if(!Zr.ok){X("HTTP "+Zr.status);return}let Z=await Zr.blob();W(Z)}catch(Ze){clearTimeout(Zto);X(Ze&&Ze.message?Ze.message:"Failed to load file")}}';

    private const MARKER = 'AbortController';

    public function handle(): int
    {
        $files = [
            base_path('vendor/filament/forms/dist/components/file-upload.js'),
            public_path('js/filament/forms/components/file-upload.js'),
        ];

        foreach ($files as $file) {
            if (! file_exists($file)) {
                $this->comment("Skipping (not found): {$file}");

                continue;
            }

            $content = file_get_contents($file);

            if (str_contains($content, self::MARKER)) {
                $this->info("Already patched: {$file}");

                continue;
            }

            if (! str_contains($content, self::SEARCH)) {
                $this->warn("Could not find the expected code to patch in: {$file} (Filament asset may have changed — check whether this patch is still needed).");

                continue;
            }

            file_put_contents($file, str_replace(self::SEARCH, self::REPLACE, $content));

            $this->info("Patched: {$file}");
        }

        return self::SUCCESS;
    }
}

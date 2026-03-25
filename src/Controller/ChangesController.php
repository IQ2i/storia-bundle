<?php

/*
 * This file is part of the UI Storia project.
 *
 * (c) Loïc Sapone <loic@sapone.fr>
 *
 * For the full copyright and license information, please view the LICENSE
 * file that was distributed with this source code.
 */

declare(strict_types=1);

namespace IQ2i\StoriaBundle\Controller;

use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\HttpFoundation\StreamedResponse;
use Symfony\Component\HttpKernel\Profiler\Profiler;

final readonly class ChangesController
{
    public function __construct(
        private string $projectDir,
    ) {
    }

    public function __invoke(Request $request, ?Profiler $profiler): StreamedResponse
    {
        if (null !== $profiler) {
            $profiler->disable();
        }

        $tmpFile = $this->projectDir.'/var/storia_reload.json';

        return new StreamedResponse(static function () use ($tmpFile): void {
            set_time_limit(0);

            while (ob_get_level() > 0) {
                ob_end_flush();
            }

            ob_implicit_flush(true);

            // Initialize from the current file so we don't replay past events on reconnect
            $lastAt = 0.0;
            if (file_exists($tmpFile)) {
                $existing = json_decode((string) file_get_contents($tmpFile), true);
                if (isset($existing['at'])) {
                    $lastAt = (float) $existing['at'];
                }
            }

            $lastPing = time();

            while (!connection_aborted()) {
                if (file_exists($tmpFile)) {
                    $data = json_decode((string) file_get_contents($tmpFile), true);
                    if (isset($data['at']) && $data['at'] > $lastAt) {
                        $lastAt = (float) $data['at'];

                        if ('stop' === ($data['type'] ?? null)) {
                            echo 'event: stop'."\n";
                            echo 'data: {}'."\n\n";
                            flush();
                            break;
                        }

                        echo 'event: reload'."\n";
                        echo 'data: '.json_encode(['type' => $data['type']])."\n\n";
                        flush();
                    }
                }

                if ((time() - $lastPing) >= 15) {
                    echo ': ping'."\n\n";
                    flush();
                    $lastPing = time();
                }

                usleep(500_000);
            }
        }, 200, [
            'Content-Type' => 'text/event-stream',
            'Cache-Control' => 'no-cache',
            'X-Accel-Buffering' => 'no',
        ]);
    }
}

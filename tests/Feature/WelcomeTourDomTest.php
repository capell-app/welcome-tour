<?php

declare(strict_types=1);

use Symfony\Component\Process\Process;

it('executes the rendered overlay controller against available missing and invalid DOM targets', function (): void {
    $html = view('capell-welcome-tour::livewire.welcome-tour-orchestrator', [
        'autoStart' => false,
        'tourIdToOpen' => 'capell_admin_welcome.dashboard',
        'currentChapterKey' => 'dashboard',
        'currentTargetSelector' => '#page',
        'targetSelectors' => ['#page', '[data-tour-id="next"]'],
        'isPreview' => true,
    ])->render();
    throw_unless($html !== '', RuntimeException::class, 'Expected a rendered overlay.');
    $document = new DOMDocument;
    $document->loadHTML($html, LIBXML_NOERROR | LIBXML_NOWARNING);
    $nodes = (new DOMXPath($document))->query('//*[@x-init]');
    $node = $nodes === false ? null : $nodes->item(0);
    throw_unless($node instanceof DOMElement, RuntimeException::class, 'Expected the Alpine controller element.');
    $script = $node->getAttribute('x-init');
    expect($script)->toContain('targetsAvailable')->not->toBeEmpty();
    $process = new Process(['node', __DIR__ . '/../Fixtures/orchestrator-dom.mjs']);
    $process->setInput($script);
    $process->mustRun();
    expect($process->getOutput())->toContain('13 assertions passed')
        ->and($html)->toContain('wire:click="snooze"', 'your saved progress and preferences are unchanged');
});

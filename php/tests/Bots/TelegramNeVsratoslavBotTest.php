<?php

declare(strict_types=1);

namespace tests\Bots;

use Bots\TelegramNeVsratoslavBot;
use pbot\Misc\Input\FileReader;
use PHPUnit\Framework\TestCase;
use pbot\Commands\CommandListener;
use Commands\HashIdCommand;
use Misc\MarkovChainsTextGenerator;

class TelegramNeVsratoslavBotTest extends TestCase
{
    private TelegramNeVsratoslavBot $bot;

    public function setUp() : void
    {
        require_once __DIR__.'/../../config.php';
        $reader = new FileReader(__DIR__.'/../input/reply_on_image.json');
        $seBot = new TelegramSecurityExpertMockBot(
            $reader,
            (new CommandListener())->addCommand('/hashid', new HashIdCommand())
        );
        $seBot->setMaxWordsCount(intval(getenv('MAX_WORDS_COUNT')));
        $this->bot = new TelegramNeVsratoslavBot();
        $this->bot->setParent($seBot);
        $this->bot->setTextGenerator(new MarkovChainsTextGenerator(CONFIG_PATH))
            ->setFontPath(__DIR__.'/../../lobster.ttf');
    }

    protected function tearDown() : void
    {
        unset($this->bot);
    }

    public function testSendPhoto(): void
    {
        $this->bot->execute();
        /**
         * @var $parent TelegramSecurityExpertMockBot
         */
        $parent = $this->bot->getParent();
        $lastResult = $parent->getLastResult();
        $this->assertCount(3, $lastResult);
        $this->assertTrue(array_key_exists('path', $lastResult), 'Result path exists');
        $this->assertTrue(array_key_exists('content', $lastResult), 'Content exists');
        $this->assertTrue(file_exists($lastResult['path']));
        $this->assertEquals(file_get_contents($lastResult['path']), $lastResult['content']);
        unlink($lastResult['path']);
    }
}
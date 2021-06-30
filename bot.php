#!/usr/bin/env php
<?php

use danog\MadelineProto\API;
use danog\MadelineProto\EventHandler;
use danog\MadelineProto\Logger;
use danog\MadelineProto\Settings;

if (!file_exists('madeline.php')) {
    copy('https://phar.madelineproto.xyz/madeline.php', 'madeline.php');
}

include 'madeline.php';

class OffersReceiver extends EventHandler
{
    private function sendMultipleMessages(array $users, array $update): Generator {
        foreach($users as $user) {
            if (isset($update['message']['media']) && $update['message']['media']['_'] !== 'messageMediaGame' && $update['message']['media']['_'] !== 'messageMediaWebPage') {
                yield $this->messages->sendMedia(['peer' => $user, 'message' => $update['message']['message'], 'media' => $update]);
            } else {
                yield $this->messages->sendMessage(['peer' => $user, 'message' => $update['message']['message'], 'parse_mode' => 'HTML']);
            }
        }
    }

    public function getReportPeers() {
        $config = file_get_contents("config.json");
        $json = json_decode($config, true);

        return $json["users"];
    }

    public function onUpdateNewChannelMessage(array $update): Generator {
        return $this->onUpdateNewMessage($update);
    }

    public function onUpdateNewMessage(array $update): Generator {

        $json = file_get_contents("config.json");
        $config = json_decode($json, true);

        if ($update['message']['_'] === 'messageEmpty' || $update['message']['out'] ?? false) {
            return;
        }

        yield $this->sendMultipleMessages($config["users"], $update);
    }
}

$settings = new Settings;
$settings->getLogger()->setLevel(Logger::LEVEL_ULTRA_VERBOSE);

$MadelineProto = new API('session.madeline', $settings);
$MadelineProto->startAndLoop(OffersReceiver::class);
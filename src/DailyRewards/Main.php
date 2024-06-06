<?php

namespace DailyRewards;

use pocketmine\plugin\PluginBase;
use pocketmine\event\Listener;
use pocketmine\event\player\PlayerJoinEvent;
use pocketmine\command\Command;
use pocketmine\command\CommandSender;
use pocketmine\player\Player;
use pocketmine\utils\Config;
use jojoe77777\FormAPI\SimpleForm;
use pocketmine\item\Item;

class Main extends PluginBase implements Listener {

    private $rewards;

    public function onEnable() {
        $this->getServer()->getPluginManager()->registerEvents($this, $this);
        $this->saveDefaultConfig();
        $this->rewards = new Config($this->getDataFolder() . "rewards.yml", Config::YAML);
    }

    public function onJoin(PlayerJoinEvent $event) {
        $player = $event->getPlayer();
        $name = $player->getName();
        if(!$this->rewards->exists($name)) {
            $this->rewards->set($name, ["last_claim" => time() - 86400, "claims_today" => 0, "last_day" => date("Y-m-d")]);
            $this->rewards->save();
        }
    }

    public function onCommand(CommandSender $sender, Command $command, string $label, array $args): bool {
        if($command->getName() == "claimreward") {
            if($sender instanceof Player) {
                $this->showRewardUI($sender);
                return true;
            } else {
                $sender->sendMessage("This command can only be used in-game.");
                return false;
            }
        }
        return false;
    }

    public function showRewardUI(Player $player) {
        $name = $player->getName();
        $data = $this->rewards->get($name);
        $currentTime = time();
        $currentDay = date("Y-m-d");

        if ($data["last_day"] !== $currentDay) {
            $data["claims_today"] = 0;
            $data["last_day"] = $currentDay;
        }

        if($data["claims_today"] < 2 && $currentTime - $data["last_claim"] >= 43200) {
            $form = new SimpleForm(function (Player $player, $data) {
                if($data === null) {
                    return;
                }
                if($data === 0) {
                    $this->giveReward($player);
                }
            });

            $form->setTitle("Daily Rewards");
            $form->setContent("Click the button below to claim your daily reward!");
            $form->addButton("Claim Reward");
            $player->sendForm($form);
        } else {
            $remaining = 43200 - ($currentTime - $data["last_claim"]);
            $player->sendMessage("You need to wait " . gmdate("H:i:s", $remaining) . " before claiming your next reward.");
        }
    }

    public function giveReward(Player $player) {
        $name = $player->getName();
        $data = $this->rewards->get($name);
        $currentTime = time();

        $data["last_claim"] = $currentTime;
        $data["claims_today"] += 1;
        $this->rewards->set($name, $data);
        $this->rewards->save();

        $reward = $this->getRandomReward();
        $player->getInventory()->addItem($reward);
        $player->sendMessage("You have claimed your daily reward!");
    }

    public function getRandomReward(): Item {
        $rewards = [
            Item::get(Item::DIAMOND, 0, 1),
            Item::get(Item::EMERALD, 0, 2),
            Item::get(Item::GOLD_INGOT, 0, 5),
            Item::get(Item::IRON_INGOT, 0, 10),
        ];

        return $rewards[array_rand($rewards)];
    }
}

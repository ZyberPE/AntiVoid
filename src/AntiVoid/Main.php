<?php

declare(strict_types=1);

namespace AntiVoid;

use pocketmine\plugin\PluginBase;
use pocketmine\event\Listener;
use pocketmine\event\player\PlayerMoveEvent;
use pocketmine\event\entity\EntityDamageEvent;
use pocketmine\player\Player;
use pocketmine\world\sound\EndermanTeleportSound;

class Main extends PluginBase implements Listener {

    /** @var array<string, int> */
    private array $cooldowns = [];

    public function onEnable(): void {
        $this->saveDefaultConfig();
        $this->getServer()->getPluginManager()->registerEvents($this, $this);
    }

    /* -------------------------
       VOID TELEPORT CHECK
    --------------------------*/

    public function onMove(PlayerMoveEvent $event): void {

        $player = $event->getPlayer();

        if(!$player->hasPermission("antivoid.use")){
            return;
        }

        $voidLevel = (int)$this->getConfig()->get("void-y-level");

        if($player->getPosition()->getY() >= $voidLevel){
            return;
        }

        $name = $player->getName();
        $cooldownTime = (int)$this->getConfig()->get("cooldown-seconds");
        $currentTime = time();

        // Check cooldown
        if(isset($this->cooldowns[$name])){
            if(($currentTime - $this->cooldowns[$name]) < $cooldownTime){
                return; // silently ignore to prevent spam
            }
        }

        // Set cooldown
        $this->cooldowns[$name] = $currentTime;

        // Teleport to spawn
        $spawn = $player->getWorld()->getSpawnLocation();
        $player->teleport($spawn);

        $player->sendMessage($this->getConfig()->get("messages")["teleport"]);

        // Play sound if enabled
        if($this->getConfig()->get("sound")["enabled"] === true){
            $player->getWorld()->addSound($player->getPosition(), new EndermanTeleportSound());
        }
    }

    /* -------------------------
       CANCEL VOID DAMAGE
    --------------------------*/

    public function onDamage(EntityDamageEvent $event): void {

        $entity = $event->getEntity();

        if(!$entity instanceof Player){
            return;
        }

        if($event->getCause() === EntityDamageEvent::CAUSE_VOID){
            $event->cancel();
        }
    }
}

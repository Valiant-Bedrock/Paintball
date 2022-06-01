<?php
/**
 *
 * Copyright (C) 2020 - 2022 | Matthew Jordan
 *
 * This program is private software. You may not redistribute this software, or
 * any derivative works of this software, in source or binary form, without
 * the express permission of the owner.
 *
 * @author sylvrs
 */
declare(strict_types=1);

namespace paintball\feed;

use libgame\game\Game;
use libgame\game\GameTrait;
use libgame\interfaces\Updatable;
use pocketmine\player\Player;
use pocketmine\utils\TextFormat;

class KillFeed implements Updatable {
	use GameTrait;

	public const MESSAGE_DURATION = 60;

	/** @var array<FeedMessage> */
	protected array $messages = [];

	public function __construct(Game $game) {
		$this->setGame($game);
	}

	/**
	 * @param string $message
	 * @param array<string, Player> $participants
	 * @return void
	 */
	public function add(string $message, array $participants): void {
		array_unshift(
			$this->messages,
			new FeedMessage(
				tickAdded: $this->getGame()->getServer()->getTick(),
				message: $message,
				participants: $participants
			)
		);
	}
	public function format(Player $player): string {
		usort($this->messages, fn(FeedMessage $a, FeedMessage $b) => $a->getTickAdded() <=> $b->getTickAdded());
		return implode(
			separator: TextFormat::EOL,
			array: array_map(fn(FeedMessage $message) => $message->getFormattedMessage($this->getGame(), $player), $this->messages)
		);
	}

	public function update(): void {
		if(count($this->messages) === 0) {
			return;
		}

		foreach ($this->messages as $key => $message) {
			if ($message->getTickAdded() + self::MESSAGE_DURATION < $this->getGame()->getServer()->getTick()) {
				unset($this->messages[$key]);
			}
		}

		$this->getGame()->executeOnAll(function(Player $player): void {
			$player->sendActionBarMessage($this->format($player));
		});
	}
}
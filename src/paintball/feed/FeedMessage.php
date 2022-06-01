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
use pocketmine\player\Player;
use pocketmine\utils\TextFormat;

class FeedMessage {

	/** @var array<string, string> */
	protected array $formattedMessages = [];

	/**
	 * @param int $tickAdded
	 * @param string $message
	 * @param array<string, Player> $participants
	 */
	public function __construct(protected int $tickAdded, protected string $message, protected array $participants) {

	}

	/**
	 * @return int
	 */
	public function getTickAdded(): int {
		return $this->tickAdded;
	}

	/**
	 * @return string
	 */
	public function getMessage(): string {
		return $this->message;
	}

	public function getFormattedMessage(Game $game, Player $player): string {
		return $this->formattedMessages[$player->getUniqueId()->getBytes()] ??= $this->format($game, $player);
	}

	public function format(Game $game, Player $player): string {
		$playerTeam = $game->getTeamManager()->getTeam($player);
		$colors = array_map(
			callback: function(Player $participant) use($game, $playerTeam): string {
				$participantTeam = $game->getTeamManager()->getTeam($participant) ?? null;
				return match(true) {
					$playerTeam !== null => $playerTeam === $participantTeam ? TextFormat::GREEN : TextFormat::RED,
					default => $participantTeam?->getColor() ?? TextFormat::WHITE
				};
			},
			array: $this->participants
		);

		$output = $this->message;
		foreach($this->participants as $key => $participant) {
			$output = str_replace("{%$key}", ($colors[$key] ?? "") . $participant->getName(), $output);
		}
		return $output;
	}

}
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

namespace paintball\command;

use libcommand\Overload;
use libcommand\parameter\types\enums\EnumParameter;
use libcommand\PlayerCommand;
use paintball\league\LeagueTeams;
use paintball\PaintballPermissions;
use pocketmine\command\CommandSender;
use pocketmine\player\Player;
use pocketmine\utils\TextFormat;

class LogoCommand extends PlayerCommand {

	public function __construct() {
		parent::__construct("logo", "Displays a team logo", "/logo <firstTeam> <secondTeam>", []);
		$this->setPermission((string) PaintballPermissions::LOGO());
		$teams = array_map(fn(LeagueTeams $team) => strtolower($team->name()), LeagueTeams::getAll());
		$this->registerOverload(new Overload("default", [new EnumParameter("firstTeam", "team", $teams), new EnumParameter("secondTeam", "team", $teams)]));

	}

	public function onExecute(CommandSender $sender, array $arguments): bool|string {
		assert($sender instanceof Player);
		/** @var string $firstTeamName */
		$firstTeamName = $arguments["firstTeam"];
		/** @var string $secondTeamName */
		$secondTeamName = $arguments["secondTeam"];
		$firstTeam = LeagueTeams::fromAlias($firstTeamName);
		$secondTeam = LeagueTeams::fromAlias($secondTeamName);
		if($firstTeam === null || $secondTeam === null) {
			return TextFormat::RED . "Unable to locate team " . $arguments["firstTeam"] . " or " . $arguments["firstTeam"];
		}
		$sender->sendTitle("{$firstTeam->getLogo()} {$secondTeam->getLogo()}", "{$firstTeam->getFormattedName()} v. {$secondTeam->getFormattedName()}");
		return true;
	}
}
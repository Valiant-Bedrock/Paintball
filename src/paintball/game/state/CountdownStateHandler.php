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

namespace paintball\game\state;

use libgame\game\GameStateHandler;
class CountdownStateHandler extends GameStateHandler {

	public function handleSetup(): void {}

	public function handleTick(int $currentStateTime): void {}

	public function handleFinish(): void {}
}
<?php
namespace game;

class GameSetting extends \Settings {

	const BOARDS = 3;

	const EMOJI_SEEDLING = '🪏';
	const EMOJI_WATERING = '🚿';
	const EMOJI_WEED = '✂️';
	const EMOJI_HARVEST = '🧺';

	const TIME_DAY = 8;
	const TIME_DAY_PREMIUM = 12;

	const TIME_HARVESTING = 4;
	const TIME_WATERING = 1;
	const TIME_WEED = 6;
	const TIME_PLANTING = 1;
	const TIME_MARKET = 1;

	const BONUS_WEED = 2;
	const BONUS_SOUP = 2;
	const BONUS_PIVOINE = 30;
	const BONUS_LUZERNE = 5;

	const ADJACENT = [
		1 => [2, 5, 6],
		2 => [1, 3, 5, 6, 7],
		3 => [2, 4, 6, 7, 8],
		4 => [3, 7, 8],
		5 => [1, 2, 6, 9, 10],
		6 => [1, 2, 3, 5, 7, 9, 10, 11],
		7 => [2, 3, 4, 6, 8, 10, 11, 12],
		8 => [3, 4, 7, 11, 12],
		9 => [5, 6, 10, 13, 14],
		10 => [5, 6, 7, 9, 11, 13, 14, 15],
		11 => [6, 7, 8, 10, 12, 14, 15, 16],
		12 => [7, 8, 11, 15, 16],
		13 => [9, 10, 14],
		14 => [9, 10, 11, 13, 15],
		15 => [10, 11, 12, 14, 16],
		16 => [11, 12, 15],
	];

}
?>

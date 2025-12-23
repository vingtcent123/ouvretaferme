<?php
namespace game;

class GameSetting extends \Settings {

	const END = '2025-12-24';
	const PROMOTION_LIMIT = '2025-12-04';

	const BOARDS = 4;

	const ADMIN = ['VingtCent'];

	const BOARDS_OPENING = [
		1 => NULL,
		2 => '2025-11-30',
		3 => '2025-12-04',
		4 => '2025-12-10',
	];

	const EMOJI_SEEDLING = '🌱';
	const EMOJI_WATERING = '🚿';
	const EMOJI_WEED = '✂️';
	const EMOJI_HARVEST = '🧺';

	const TIME_DAY = 8;
	const TIME_DAY_PREMIUM = 11;

	const TIME_HARVESTING = 4;
	const TIME_WATERING = 1;
	const TIME_WEED = 6;
	const TIME_SEEDLING = 1.5;

	const BONUS_MOTIVATION = 1;
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

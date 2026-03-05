<?php
namespace receipts;

class BookLib extends BookCrud {

	public static function getPropertiesCreate(): array {
		return [];
	}

	public static function getAll(): \Collection {

		return Book::model()
			->select(Book::getSelection())
			->sort([
				new \Sql('FIELD(status, "'.Book::ACTIVE.'", "'.Book::INACTIVE.'") ASC'),
				'id' => SORT_ASC
			])
			->getCollection(index: 'id');

	}

	public static function getActive(): Book {

		return Book::model()
			->select(Book::getSelection())
			->whereStatus(Book::ACTIVE)
			->get();

	}

	public static function countPending(Book $e): Line {

		static $count = Line::model()
			->select([
				'draft' => new \Sql('SUM(status = "'.Line::DRAFT.'")', 'int'),
				'balance' => new \Sql('SUM(source = "'.Line::BALANCE.'")', 'int'),
				'firstDraft' => new \Sql('MIN(date)', 'string'),
				'lastDraft' => new \Sql('MAX(date)', 'string'),
			])
			->whereBook($e)
			->whereStatus(Line::DRAFT)
			->get();

		$count['draft'] ??= 0;
		$count['balance'] ??= 0;
		$count['firstDraft'] ??= NULL;
		$count['lastDraft'] ??= NULL;

		return $count;

	}

	public static function delete(Book $e): void {

		Book::model()->beginTransaction();

			LineLib::deleteWaiting($e);

			Book::model()
				->update($e, [
					'status' => Book::INACTIVE
				]);

		Book::model()->commit();

	}

	public static function close(Book $e, string $date): void {

		Book::model()->beginTransaction();

			// Vérifications de sécurité dans la transaction pour garantir la consistence des données
			if(
				Line::model()
					->whereBook($e)
					->whereStatus(Line::DRAFT)
					->whereDate('<=', $date)
					->exists()
			) {
				Book::model()->rollBack();
				return;
			}

			Book::model()
				->where('closedAt', '<', $date)
				->update([
					'closedAt' => $date
				]);

		Book::model()->commit();

	}

}
?>

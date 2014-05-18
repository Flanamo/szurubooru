<?php
use \Chibi\Sql as Sql;
use \Chibi\Database as Database;

final class CommentModel extends AbstractCrudModel
{
	public static function getTableName()
	{
		return 'comment';
	}

	protected static function saveSingle($comment)
	{
		$comment->validate();
		$comment->getPost()->removeCache('comment_count');

		Database::transaction(function() use ($comment)
		{
			self::forgeId($comment);

			$bindings = [
				'text' => $comment->getText(),
				'post_id' => $comment->getPostId(),
				'comment_date' => $comment->getCreationTime(),
				'commenter_id' => $comment->getCommenterId()];

			$stmt = new Sql\UpdateStatement();
			$stmt->setTable('comment');
			$stmt->setCriterion(new Sql\EqualsFunctor('id', new Sql\Binding($comment->getId())));

			foreach ($bindings as $key => $val)
				$stmt->setColumn($key, new Sql\Binding($val));

			Database::exec($stmt);
		});

		return $comment;
	}

	protected static function removeSingle($comment)
	{
		Database::transaction(function() use ($comment)
		{
			$comment->getPost()->removeCache('comment_count');
			$stmt = new Sql\DeleteStatement();
			$stmt->setTable('comment');
			$stmt->setCriterion(new Sql\EqualsFunctor('id', new Sql\Binding($comment->getId())));
			Database::exec($stmt);
		});
	}



	public static function getAllByPostId($key)
	{
		$stmt = new Sql\SelectStatement();
		$stmt->setColumn('comment.*');
		$stmt->setTable('comment');
		$stmt->setCriterion(new Sql\EqualsFunctor('post_id', new Sql\Binding($key)));

		$rows = Database::fetchAll($stmt);
		if ($rows)
			return self::spawnFromDatabaseRows($rows);
		return [];
	}



	public static function preloadCommenters($comments)
	{
		self::preloadOneToMany($comments,
			function($comment) { return $comment->getCommenterId(); },
			function($user) { return $user->getId(); },
			function($userIds) { return UserModel::getAllByIds($userIds); },
			function($comment, $user) { return $comment->setCache('commenter', $user); });
	}

	public static function preloadPosts($comments)
	{
		self::preloadOneToMany($comments,
			function($comment) { return $comment->getPostId(); },
			function($post) { return $post->getId(); },
			function($postIds) { return PostModel::getAllByIds($postIds); },
			function($comment, $post) { $comment->setCache('post', $post); });
	}
}

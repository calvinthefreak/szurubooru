<?php
class GetPostThumbJob extends AbstractJob
{
	const WIDTH = 'width';
	const HEIGHT = 'height';

	public function execute()
	{
		$name = $this->getArgument(self::POST_NAME);
		$width = $this->hasArgument(self::WIDTH) ? $this->getArgument(self::WIDTH) : null;
		$height = $this->hasArgument(self::HEIGHT) ? $this->getArgument(self::HEIGHT) : null;

		$path = PostModel::getThumbCustomPath($name, $width, $height);
		if (!file_exists($path))
		{
			$path = PostModel::getThumbDefaultPath($name, $width, $height);
			if (!file_exists($path))
			{
				$post = PostModel::findByIdOrName($name);

				if ($post->hidden)
					Access::assert(Privilege::ListPosts, 'hidden');
				Access::assert(Privilege::ListPosts);
				Access::assert(Privilege::ListPosts, PostSafety::toString($post->safety));

				$post->generateThumb($width, $height);

				if (!file_exists($path))
				{
					$path = getConfig()->main->mediaPath . DS . 'img' . DS . 'thumb.jpg';
					$path = TextHelper::absolutePath($path);
				}
			}
		}

		if (!is_readable($path))
			throw new SimpleException('Thumbnail file is not readable');

		return new ApiFileOutput($path, 'thumbnail.jpg');
	}

	public function requiresPrivilege()
	{
		//manually enforced in execute when post is retrieved
		return false;
	}

	public function requiresAuthentication()
	{
		return false;
	}

	public function requiresConfirmedEmail()
	{
		return false;
	}
}

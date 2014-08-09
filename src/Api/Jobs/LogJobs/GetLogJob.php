<?php
class GetLogJob extends AbstractJob implements IPagedJob
{
	protected $pager;

	public function __construct()
	{
		$this->pager = new JobPager($this);
		$this->pager->setPageSize(Core::getConfig()->browsing->logsPerPage);
	}

	public function getPager()
	{
		return $this->pager;
	}

	public function execute()
	{
		$pageSize = $this->pager->getPageSize();
		$page = $this->pager->getPageNumber();
		$name = $this->getArgument(JobArgs::ARG_LOG_ID);
		$query = $this->hasArgument(JobArgs::ARG_QUERY)
			? $this->getArgument(JobArgs::ARG_QUERY)
			: '';

		$page = max(1, intval($page));
		$path = $this->getPath($name);
		$lines = $this->loadLines($path);

		if (!empty($query))
		{
			$lines = array_filter($lines, function($line) use ($query)
			{
				return stripos($line, $query) !== false;
			});
		}

		$lineCount = count($lines);
		$lines = array_slice($lines, ($page - 1) * $pageSize, $pageSize);

		return $this->pager->serialize($lines, $lineCount);
	}

	public function getRequiredArguments()
	{
		return JobArgs::Conjunction(
			$this->pager->getRequiredArguments(),
			JobArgs::ARG_LOG_ID,
			JobArgs::Optional(JobArgs::ARG_QUERY));
	}

	public function getRequiredMainPrivilege()
	{
		return Privilege::ViewLog;
	}

	public function getRequiredSubPrivileges()
	{
		return null;
	}

	public function isAuthenticationRequired()
	{
		return false;
	}

	public function isConfirmedEmailRequired()
	{
		return false;
	}

	private function getPath($name)
	{
		$name = str_replace(['/', '\\'], '', $name);
		return TextHelper::absolutePath(dirname(Core::getConfig()->main->logsPath) . DS . $name);
	}

	private function loadLines($path)
	{
		if (!file_exists($path))
			throw new SimpleNotFoundException('Specified log doesn\'t exist');

		$lines = file_get_contents($path);
		$lines = trim($lines);
		$lines = explode(PHP_EOL, str_replace(["\r", "\n"], PHP_EOL, $lines));
		$lines = array_reverse($lines);
		return $lines;
	}
}

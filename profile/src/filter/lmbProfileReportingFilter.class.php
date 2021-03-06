<?php
lmb_require('limb/filter_chain/src/lmbInterceptingFilter.interface.php');
lmb_require('limb/dbal/src/drivers/lmbAuditDbConnection.class.php');
lmb_require('limb/cache2/src/drivers/lmbCacheLoggedConnection.class.php');

set_include_path(dirname(__FILE__) . '/../../lib/PEAR' . PATH_SEPARATOR . get_include_path());

class lmbProfileReportingFilter implements lmbInterceptingFilter
{
  protected $start_time;
  protected $highlight = false;

  public function run($filter_chain)
  {
    $is_profile_enabled = lmbToolkit::instance()->isProfilingEnabled();

    if($is_profile_enabled)
    {
      $toolkit = lmbToolkit :: instance();
      $conn = $toolkit->getDefaultDbConnection();
      if (!$conn instanceof lmbAuditDbConnection)
      {
        $conn = new lmbAuditDbConnection($conn);
        $toolkit->setDefaultDbConnection($conn);
      }
      $this->start_time = microtime(true);

      $cache = $toolkit->getCache();
      $cache = new lmbCacheLoggedConnection($cache, 'default');
      $toolkit->setCache('default', $cache);
    }

    $filter_chain->next();

    if($is_profile_enabled)
    {
     $reporter = lmbToolkit::instance()->getProfileReporter();

     $reporter->setScriptStatistic(
       microtime(true) - $this->start_time,
       memory_get_usage(),
       memory_get_peak_usage()
      );

      foreach ($conn->getStats() as $key => $info)
        $reporter->addSqlQuery($info);

      foreach ($cache->getRuntimeStats() as $key => $info)
        $reporter->addCacheQuery($info);

      lmbToolkit::instance()->getResponse()->append($reporter->getReport());
    }
  }
}
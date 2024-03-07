<?php

require_once('common.php');

authentication_require_role('admin');

/* © 2013 Mantas Mikulėnas <grawity@gmail.com>*/

//Iterator extends Traversable {
//    void    rewind()
//    boolean valid()
//    void    next()
//    mixed   current()
//    scalar  key()
//}
//calls:  rewind, valid==true, current, key
//    next, valid==true, current, key
//    next, valid==false


class Journal implements Iterator
{
    private array $filter;
    private $startpos;
    private $proc;
    private $stdout;
    private $entry;

    public static function _join_argv($argv): string
    {
        return implode(" ",
            array_map(function ($a) {
                return strlen($a) ? escapeshellarg($a) : "''";
            },
                $argv)
        );
    }

    public function __construct($filter=[], $cursor=null)
    {
        $this->filter = $filter;
        $this->startpos = $cursor;
    }

    public function _close_journal()
    {
        if ($this->stdout) {
            fclose($this->stdout);
            $this->stdout = null;
        }
        if ($this->proc) {
            proc_close($this->proc);
            $this->proc = null;
        }
        $this->entry = null;
    }

    public function _open_journal($filter=[], $cursor=null): bool
    {
        if ($this->proc) {
            $this->_close_journal();
        }

        $this->filter = $filter;
        $this->startpos = $cursor;

        $cmd = ["journalctl", "-o", "json"];
        if ($cursor) {
            $cmd[] = "-c";
            $cmd[] = $cursor;
        }
        $cmd = array_merge($cmd, $filter);
        $cmd = self::_join_argv($cmd); //print($cmd);

        $fdspec = [
            0 => ["file", "/dev/null", "r"],
            1 => ["pipe", "w"],
            2 => ["file", "/dev/null", "w"],
        ];

        $this->proc = proc_open($cmd, $fdspec, $fds);
        if (!$this->proc) {
            return false;
        }
        $this->stdout = $fds[1]; //echo stream_get_contents($this->stdout);
        return true;
    }

    public function seek($cursor)
    {
        $this->_open_journal($this->filter, $cursor);
    }

    public function rewind(): void
    {
        $this->seek($this->startpos);
    }

    public function next(): void
    {
        $line = fgets($this->stdout);
        if ($line === false) {
            $this->entry = false;
        } else {
            $this->entry = json_decode($line);
        }
    }

    public function valid(): bool
    {
        return ($this->entry !== false);
        /* null is valid, it just means next() hasn't been called yet */
    }

    public function current(): mixed
    {
        if (!$this->entry) {
            $this->next();
        }
        return $this->entry;
    }

    public function key(): mixed
    {
        if (!$this->entry) {
            $this->next();
        }
        return $this->entry->__CURSOR;
    }
}
if (isset($_GET['tab'])) {
    $_SESSION['tab'] = $_GET['tab'];
}
if (!isset($_SESSION['tab'])) {
    $_SESSION['tab'] = 'auth_logs';
}

if (isset($_POST['date'])) {
    $logdate = $_POST['date'];
} else {
    $logdate = date('Y-m-d 00:00:00');
}

if (isset($_POST['search'])) {
    $search = $_POST['search'];
} else {
    $search = '';
}

$cursor = $_POST['cursor'] ?? null;
$reverse = $_POST['prev'] ?? null;

$CONF = Config::getInstance()->getAll();
$tLog = array();
$first_cursor = '';
$last_cursor = '';

$service = 'postfix@-';
switch ($_SESSION['tab']) {
    case 'dovecot_logs':
        $service = 'dovecot';
        break;
    case 'postfix_logs':
        $service = 'postfix@-';
        break;
}

$a = new Journal();
$filter = array('-u',$service);
if (!$cursor) {
    $filter[] = '-S';
    $filter[] = $logdate;
}
if ($reverse) {
    $filter[] = '-r';
}
if ($search != '') {
    $filter[] = '-g';
    $filter[] = $search;
} else {
    $filter[] = '-n';
    $filter[] = $CONF['page_size'];
}
$a->_open_journal($filter,$cursor);
foreach ($a as $cursor => $item) {
    if ($first_cursor == '') {
        $first_cursor = $item->__CURSOR;
    }
    $last_cursor = $item->__CURSOR;
    $tLog[$cursor]['time'] = $item->SYSLOG_TIMESTAMP;
    $tLog[$cursor]['message'] = htmlspecialchars($item->MESSAGE);
    $tLog[$cursor]['level'] = $item->PRIORITY;
}
$a->_close_journal();

$hidePrev = ((count($tLog) < $CONF['page_size']) && $reverse) || $search!='';
$hideNext = ((count($tLog) < $CONF['page_size']) && !$reverse) || $search!='';
//get url
$url=explode("?",(isset($_SERVER['HTTPS']) && $_SERVER['HTTPS'] === 'on' ? "https" : "http") . "://$_SERVER[HTTP_HOST]$_SERVER[REQUEST_URI]")[0];

$smarty = PFASmarty::getInstance();
$smarty->assign('date', $logdate);
$smarty->assign('hidePrev', $hidePrev);
$smarty->assign('hideNext', $hideNext);
$smarty->assign('search', $search);
$smarty->assign('first_cursor', $first_cursor);
$smarty->assign('last_cursor', $last_cursor);
$smarty->assign('tab', $_SESSION ['tab']);
$smarty->assign('tLog', $tLog, false);
$smarty->assign('url',$url);
$smarty->assign('smarty_template', 'maillog');
$smarty->display('index.tpl');

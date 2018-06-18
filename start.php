<?php
Class Config {
    public $files = array();
    protected $eng = false;
    public $txt = '';
    protected $debug = true;
    protected $log = false;
    protected $files_commit = array();
    protected $hash = array();
    protected $directories = '';
    protected $stop_ext = array('log', '_export','Temp','.','..','.git','.idea');

    protected $git_url = '';
    protected $git_token = '';
    protected $git_name_project = '';
    public $git_name_branch = 'master';
    public $git_project = array();

    protected $te_group_id = '';
    protected $te_token = '';
    protected $proxy = array(
        "on" => true,
        "host" => "",
        "port" => ,
        "user" => "",
        "pass" => ""
    );


    protected function translit($s) {
      $s = (string) $s; // преобразуем в строковое значение
      $s = strip_tags($s); // убираем HTML-теги
      $s = str_replace(array("\n", "\r"), " ", $s); // убираем перевод каретки
      $s = preg_replace("/\s+/", ' ', $s); // удаляем повторяющие пробелы
      $s = trim($s); // убираем пробелы в начале и конце строки
      $s = function_exists('mb_strtolower') ? mb_strtolower($s) : strtolower($s); // переводим строку в нижний регистр (иногда надо задать локаль)
      $s = strtr($s, array('а'=>'a','б'=>'b','в'=>'v','г'=>'g','д'=>'d','е'=>'e','ё'=>'e','ж'=>'j','з'=>'z','и'=>'i','й'=>'y','к'=>'k','л'=>'l','м'=>'m','н'=>'n','о'=>'o','п'=>'p','р'=>'r','с'=>'s','т'=>'t','у'=>'u','ф'=>'f','х'=>'h','ц'=>'c','ч'=>'ch','ш'=>'sh','щ'=>'shch','ы'=>'y','э'=>'e','ю'=>'yu','я'=>'ya','ъ'=>'','ь'=>''));
      $s = preg_replace("/[^0-9a-z-_ ]/i", "", $s); // очищаем строку от недопустимых символов
      $s = str_replace(" ", "-", $s); // заменяем пробелы знаком минус
      return $s; // возвращаем результат
    }
    public  function sendOne($msg) {
        if ($this->proxy['on']) {
            $url = "https://api.telegram.org/" . $this->te_token . "/sendMessage";
            $ch = curl_init();
            curl_setopt($ch, CURLOPT_URL, $url);
            curl_setopt($ch, CURLOPT_POST, 1);
            curl_setopt($ch, CURLOPT_PROXYTYPE, CURLPROXY_SOCKS5);
            curl_setopt($ch, CURLOPT_PROXY, 'socks5://' . $this->proxy['user'] . ':' . $this->proxy['pass'] . '@' . $this->proxy['host'] . ':' . $this->proxy['port']);
            curl_setopt($ch, CURLOPT_POSTFIELDS, json_encode(["chat_id" => $this->te_group_id, "text" => $msg, "parse_mode" => 'HTML', "disable_web_page_preview" => 1,
                "disable_notification" => true,]));
            curl_setopt($ch, CURLOPT_HTTPHEADER, ["Content-type: application/json"]);
            //curl_setopt($ch, CURLOPT_SSL_VERIFYHOST, FALSE);
            curl_setopt($ch, CURLOPT_SSL_VERIFYPEER, FALSE);
            curl_setopt($ch, CURLOPT_RETURNTRANSFER, TRUE);
            curl_setopt($ch, CURLOPT_USERAGENT, 'Mozilla/5.0 (Windows; U; Windows NT 5.1; en-US; rv:1.8.1.13) Gecko/20080311 Firefox/2.0.0.13');
            $out = curl_exec($ch);
            if($out === false)
            {
                echo 'Ошибка curl: ' . curl_error($ch);
            }
            else
            {
                echo 'Операция завершена без каких-либо ошибок';
                var_dump($out);
            }
            curl_close($ch);

        }
        else {
            $params = [
                "chat_id" => $this->te_group_id,
                "text" => $msg,
                "parse_mode" => 'HTML',
                "disable_web_page_preview" => 1,
                "disable_notification" => true,
            ];
            $aContext = array(
                'http' => array(
                    'method' => 'POST',
                    'content' => http_build_query($params),
                    'header' => "Content-type: application/x-www-form-urlencoded",
                    'ignore_errors' => true,
                ),
            );
            $cxContext = stream_context_create($aContext);
            $query = "https://api.telegram.org/" . $this->te_token . "/sendMessage";
            file_get_contents($query, false, $cxContext);
        }
    }

    public function sendFile($path) {

        $url = "https://api.telegram.org/".$this->te_token."/sendDocument";
        echo realpath($path);
        $document = new CURLFile(realpath($path));
        $ch = curl_init();
        curl_setopt($ch, CURLOPT_URL, $url);
        curl_setopt($ch, CURLOPT_POST, 1);
        curl_setopt($ch, CURLOPT_POSTFIELDS, ["chat_id" => $this->te_group_id, "document" => $document]);
        curl_setopt($ch, CURLOPT_HTTPHEADER, ["Content-Type:multipart/form-data"]);
        curl_setopt($ch, CURLOPT_SSL_VERIFYPEER, FALSE);
        curl_setopt($ch, CURLOPT_RETURNTRANSFER, TRUE);
        $out = curl_exec($ch);
        curl_close($ch);
        print_r($out);


    }
    public function log($class, $method, $line, $msg, $level)
    {
        if($this->eng) $msg = $this->translit($msg);
        if ($this->debug) {
            switch ($level) {
                case 'e':
                    echo "\033[1;30m\033[41m[" . date('d:m:Y H:i:s') . "]\033[0m [" . $class . "][" . $method . "][" . $line . "]" . ">>> " . $msg . PHP_EOL;
                    break;
                case 'n':
                    echo "\033[1;30m\033[43m[" . date('d:m:Y H:i:s') . "]\033[0m [" . $class . "][" . $method . "][" . $line . "]" . ">>> " . $msg . PHP_EOL;
                    break;
                case 'a':
                    echo "\033[7;30m\033[42m[" . date('d:m:Y H:i:s') . "]\033[0m[" . $class . "][" . $method . "][" . $line . "]" . ">>> " . $msg . PHP_EOL;
                    break;
            }
        }
        if ($this->log) {
            switch ($level) {
                case 'e':
                    $this->txt .= "[E][" . date('d:m:Y H:i:s') . "][" . $class . "][" . $method . "][" . $line . "]" . ">>> " . $msg . "\n";
                    break;
                case 'n':
                    $this->txt .= "[N][" . date('d:m:Y H:i:s') . "][" . $class . "][" . $method . "][" . $line . "]" . ">>> " . $msg . "\n";
                    break;
                case 'a':
                    $this->txt .= "[A][" . date('d:m:Y H:i:s') . "][" . $class . "][" . $method . "][" . $line . "]" . ">>> " . $msg . "\n";
                    break;
            }
        }
    }
    public function saveLog()
    {
        if (!empty($this->txt)) file_put_contents(__DIR__ . '/' . date("Y.m.d Hi") . ".log", $this->txt);
    }
}
Class Manage extends Config {
    private function scan($dir)
    {
        foreach (scandir($dir,0) as $key => $value)
        {
            if (in_array($value, $this->stop_ext)) continue;
            else if (is_file($dir.DIRECTORY_SEPARATOR.$value)) {
                $file = pathinfo($dir.DIRECTORY_SEPARATOR.$value);
                $ext = $file['extension'] ?? null;
                $this->files[] = array(
                    'file' => $value,
                    'ext' => $ext,
                    'size' => filesize($dir.DIRECTORY_SEPARATOR.$value),
                    'time' => filectime($dir.DIRECTORY_SEPARATOR.$value),
                    'dir' => $dir.DIRECTORY_SEPARATOR,
                    'dir_short' => str_replace($this->directories, '', $dir.DIRECTORY_SEPARATOR),
                    'hash' => md5($value.'|'.$ext.'|'.filesize($dir.DIRECTORY_SEPARATOR.$value).'|'.filectime($dir.DIRECTORY_SEPARATOR.$value).'|'.$dir.DIRECTORY_SEPARATOR)
                );

            }
            else if (is_dir($dir.DIRECTORY_SEPARATOR.$value)) {
                $this->scan($dir.DIRECTORY_SEPARATOR.$value);
            }
        }
    }
    private function getHash() {
        $this->hash = file(__DIR__.DIRECTORY_SEPARATOR.'hf',FILE_IGNORE_NEW_LINES);
    }
    private function setHash()
    {
        $hash = '';
        foreach ($this->files as $file){
            $hash .= md5($file['file'].'|'.$file['ext'].'|'.$file['size'].'|'.$file['time'].'|'.$file['dir'])."\n";
        }
        file_put_contents(__DIR__.DIRECTORY_SEPARATOR.'hf',$hash);
    }
    private function diffHash() {
        foreach ($this->files as $k => $v) {
            if (!in_array($v['hash'],$this->hash)) {
                $this->files[$k]['commit'] = 1;
            }
            else {
                $this->files[$k]['commit'] = 0;
            }
        }
    }
    private function filter() {
        foreach ($this->files as $k => $v) {
            if ($v['commit']==1 and !empty($v['ext']) and !in_array($v['ext'],$this->stop_ext)) {
                $this->files_commit[] =  $this->files[$k];
            }
        }
    }
    public function getFiles()
    {
        $this->scan($this->directories);
        $this->getHash();
        $this->diffHash();
        $this->filter();
        $this->setHash();
        $this->log(__CLASS__,__METHOD__,__LINE__,"Для коммита есть: ".count($this->files_commit),'a');
        return $this->files_commit;
    }
}
Class GitLab extends Config {
    public function getProject(){
        $data =  json_decode(file_get_contents($this->git_url.'projects?private_token='.$this->git_token));
        foreach ($data as $project) {
            if($this->git_name_project !== $project->name) continue;
            $this->git_project = array(
                'id' => $project->id,
                'name' => $project->name,
                'desc' => $project->description,
                'default_branch' => $project->description,
            );
            //print_r($project);
        }
    }
    public function getBranches($id) {
        $data =  json_decode(file_get_contents($this->git_url.'projects/'.$id.'/repository/branches?private_token='.$this->git_token));
        foreach ($data as $project) {
            if ($project->name == $this->git_name_branch) return true;
        }
        $this->newBranch($id);
    }
    public function newProject(){
        $post_data = array (
            "name" => $this->git_name_project,
            "path" => $this->git_name_project,
            'namespace_id' => 3,
            'default_branch' => $this->git_name_branch
        );

        $header = array(
            'Private-Token: '.$this->git_token
        );

        $ch = curl_init();
        curl_setopt($ch, CURLOPT_URL, $this->git_url.'projects');
        curl_setopt($ch, CURLOPT_RETURNTRANSFER, 1);
        curl_setopt($ch, CURLOPT_POST, 1);
        curl_setopt($ch, CURLOPT_HTTPHEADER, $header);
        curl_setopt($ch, CURLOPT_POSTFIELDS, $post_data);
        $output = curl_exec($ch);
        curl_close($ch);
        $this->log(__CLASS__,__METHOD__,__LINE__,$output,'a');
    }
    public function newBranch($id) {
        $post_data = array (
            "id" => $id,
            "branch" => $this->git_name_branch,
            'ref' => 'master'
        );

        $header = array(
            'Private-Token: '.$this->git_token
        );

        $ch = curl_init();
        curl_setopt($ch, CURLOPT_URL, $this->git_url.'projects/'.$id.'/repository/branches');
        curl_setopt($ch, CURLOPT_RETURNTRANSFER, 1);
        curl_setopt($ch, CURLOPT_POST, 1);
        curl_setopt($ch, CURLOPT_HTTPHEADER, $header);
        curl_setopt($ch, CURLOPT_POSTFIELDS, $post_data);
        $output = curl_exec($ch);
        curl_close($ch);
        $this->log(__CLASS__,__METHOD__,__LINE__,$output,'a');
    }
    public function getFileIsset($id, $file_path) {
        # GET /projects/:id/repository/files/:file_path
        # http://dev.log-os.ru:8280/api/v4/projects/146/repository/files/Config/Config.php?private_token=z8fkepzyGGyemf-wx8SQ&ref=master
        $data =  json_decode(@file_get_contents($this->git_url.'projects/'.$id.'/repository/files/'.$file_path.'?private_token='.$this->git_token.'&ref='.$this->git_name_branch));
        if(!empty($data)) return true;
        else return false;
    }
    public function commit($id, $files, $new=null){
        if($new) {
            # новый проект, грузим все
            $post_data = array (
                "branch" => $this->git_name_branch,
                "commit_message" => "some commit message",
                "actions" => array()
            );
            foreach ($files as $file) {
                $post_data['actions'][] = array(
                    "action" => "create",
                    "file_path" => str_replace('\\','/',$file['dir_short'].$file['file']),
                    "content" => "first upload"
                );
            }
        }
        else {
            # коммитим только изменения
            $post_data = array (
                "branch" => $this->git_name_branch,
                "commit_message" => "some commit message",
                "actions" => array()
            );
            foreach ($files as $file) {
                $full_path = str_replace('\\','/',$file['dir_short'].$file['file']);
                if($this->getFileIsset($id, $full_path)) $type = 'update';
                else $type = 'create';
                $post_data['actions'][] = array(
                    "action" => $type,
                    "file_path" => $full_path,
                    "content" => "first upload"
                );
            }
        }
        $header = array(
            'Private-Token: '.$this->git_token,
            'Content-Type: application/json'
        );
        $ch = curl_init();
        curl_setopt($ch, CURLOPT_URL, $this->git_url.'projects/'.$id.'/repository/commits');
        curl_setopt($ch, CURLOPT_RETURNTRANSFER, 1);
        curl_setopt($ch, CURLOPT_POST, 1);
        curl_setopt($ch, CURLOPT_HTTPHEADER, $header);
        curl_setopt($ch, CURLOPT_POSTFIELDS, json_encode($post_data));
        $output = curl_exec($ch);
        curl_close($ch);
        $this->log(__CLASS__,__METHOD__,__LINE__,$output,'a');
    }
}
$manage = new Manage();
$git = new GitLab();
$files = $manage->getFiles();
$git->getProject();
if (empty($git->git_project)) {
    $git->newProject();
    $git->getProject();
    $git->commit($git->git_project['id'], $manage->files, 1);
    echo "newProject";
}
else {
    if($files) {
        $git->getBranches($git->git_project['id']);
        $git->commit($git->git_project['id'], $files);
        echo "commit";
    }
}
echo $manage->txt;
$manage->sendOne('test');

<?php

namespace bxrocketeer;

class Git extends \Rocketeer\Abstracts\AbstractTask
{
    /**
     * @var string
     */
    protected $description = 'Prepare hosting for working with git';

    public function execute()
    {
        $this->explainer->line($this->description);
        $this->addGitToKnownHosts($this);
        $this->createOrShowSshKey($this);

        return true;
    }

    /**
     * Задает ссылку на репозиторий в список хостов, которым можно доверять.
     */
    protected function addGitToKnownHosts($task)
    {
        $repo = $task->rocketeer->getOption('scm.repository');
        if (!$repo) {
            return null;
        }
        $arRepo = parse_url($repo);
        if (empty($arRepo['scheme']) || $arRepo['scheme'] !== 'ssh') {
            return null;
        }
        $isKnownHostsExists = trim($task->runRaw('[ -f ~/.ssh/known_hosts ] && echo 1')) === '1';
        if (!$isKnownHostsExists) {
            $task->run('touch ~/.ssh/known_hosts');
        }
        $hostname = $arRepo['host'];
        $task->runForCurrentRelease([
            "ssh-keygen -R {$hostname}",
            'ssh-keyscan -t rsa'.(!empty($arRepo['port']) ? " -p{$arRepo['port']}" : '')." -H {$hostname} >> ~/.ssh/known_hosts",
        ]);
    }

    /**
     * Выводит открытую часть ключа на экран, если ключа нет, то пробует создать.
     */
    protected function createOrShowSshKey($task)
    {
        $isKeyExists = trim($task->runRaw('[ -f ~/.ssh/id_rsa.pub ] && echo 1')) === '1';
        if (!$isKeyExists) {
            $task->runRaw('ssh-keygen -t rsa -N "" -f ~/.ssh/id_rsa');
        }
        $key = $task->runRaw('cat ~/.ssh/id_rsa.pub');
        $task->command->info('Hosting ssh key is: '.$key);
    }
}

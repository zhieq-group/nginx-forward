<?php

class App
{

    /**
     * @var
     */

    protected $ngProxy;

    /**
     * @var array
     */

    protected $defaultProxyConfigs = [
        ['key' => 'client_max_body_size', 'value' => '10M'],
        ['key' => 'proxy_set_header', 'value' => 'X-Real-IP $remote_addr'],
        ['key' => 'proxy_set_header', 'value' => 'Host $proxy_host'],
        ['key' => 'proxy_set_header', 'value' => 'X-Forwarded-Host $host'],
        ['key' => 'proxy_set_header', 'value' => 'X-Forwarded-For $proxy_add_x_forwarded_for'],
        ['key' => 'proxy_set_header', 'value' => 'X-Forwarded-Proto $scheme'],
    ];

    /**
     * @param $key
     * @param null $default
     * @return null
     */

    protected function env($key, $default = null)
    {
        return isset($_ENV[$key]) ? $_ENV[$key] : $default;
    }

    /**
     *
     */

    public function handle()
    {
        $proxyList = $this->env('NG_FORWARD', null);
        $this->info('ng proxy list:' . $proxyList);
        if ($proxyList === null) {
            $this->info('no ng proxy config.');
            exit;
        }
        try {
            $proxyList = json_decode(base64_decode($proxyList), true);
            foreach ($proxyList as $proxy) {
                $this->ngProxy .= 'location ' . $proxy['path'] . ' {' . "\n" . $this->buildContent($proxy) . '}' . "\n";
            }
            file_put_contents($this->ngConfigFile(), str_replace('#proxy_list', $this->ngProxy, file_get_contents($this->ngConfigFile())));
        } catch (\Exception $exception) {
            $this->info('ng proxy format error.');
        }
    }

    /**
     * @param $proxy
     * @return string
     */

    protected function buildContent($proxy)
    {
        $type = isset($proxy['type']) ? $proxy['type'] : 'proxy';
        $method = 'build' . ucfirst($type) . 'Content';
        return method_exists($this, $method) ? $this->{$method}($proxy) : null;
    }

    /**
     * @param $proxy
     * @return string
     */

    protected function buildProxyContent($proxy)
    {
        $baseContent = 'proxy_pass http://' . $proxy['ip'] . ':' . $proxy['port'] . ';' . "\n";
        return $baseContent . (isset($proxy['proxyConfigs']) ? $this->buildConfigs($proxy['proxyConfigs'], (isset($proxy['proxyBase']) ? $proxy['proxyBase'] : true)) : $this->buildConfigs([]));
    }

    /**
     * @param $proxy
     * @return mixed
     */

    protected function buildRewriteContent($proxy)
    {
        $rewriteString = '';
        $rewriteRules = isset($proxy['rewriteRules']) ? $proxy['rewriteRules'] : [];
        foreach ($rewriteRules as $rule) {
            $rewriteString .= 'rewrite ' . $rule['key'] . ' ' . $rule['value'] . ';' . "\n";
        }
        return $rewriteString;
    }

    /**
     * @param array $configs
     * @param bool $mergeBase
     * @return string
     */

    protected function buildConfigs($configs = [], $mergeBase = true)
    {
        $configs = $mergeBase === true ? array_merge($this->defaultProxyConfigs, $configs) : $configs;
        $configString = '';
        foreach ($configs as $config) {
            $configString .= $config['key'] . ' ' . $config['value'] . ';' . "\n";
        }
        return $configString;
    }

    /**
     * @return string
     */

    protected function ngConfigFile()
    {
        return __DIR__ . '/nginx.conf';
    }

    /**
     * @param $message
     */

    protected function info($message)
    {
        echo $message . "\n";
    }

}

(new App())->handle();

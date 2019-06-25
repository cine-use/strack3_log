<?php

namespace app\Clients;

use Server\CoreBase\CoreBase;

class WechatClient extends CoreBase
{
    /**
     * 初始化EasyWeChat
     * @var \EasyWeChat\Work\Application
     */
    protected $weChatApp = null;

    // 企业公众号配置
    protected $weChatConfig = [];

    /**
     * @var \Redis
     */
    protected $redis;

    // 图文消息内容
    protected $weChatMessage = [
        'msgtype' => 'mpnews',
        'touser' => '', // 通知人员
        'agentid' => '', // 应用id
        "mpnews" => [
            "articles" => []
        ],
        "safe" => 1 // 是否为加密信息（水印和限制转发）
    ];

    /**
     * 初始化微信配置
     */
    public function init()
    {
        $this->redis = $this->loader->redis("redisPool", $this);
        if ($this->config['message']['wechat']["enable"]) {
            $this->weChatConfig = $this->config['message']['wechat'];
            $this->weChatApp = \EasyWeChat\Factory::work($this->weChatConfig);
        }
    }

    /**
     * 生成文章
     * @param $data
     * @param string $mediaPath
     */
    protected function addArticlesItem($data, $mediaPath = '')
    {
        $thumbMediaId = $this->getWeChatCover($mediaPath);
        $this->weChatMessage["mpnews"]["articles"][] = [
            "title" => $data["title"], // 标题
            "thumb_media_id" => $thumbMediaId, // 封面图像id
            "author" => "strack", // 作者
            "content_source_url" => $data["detail_url"], // 原文地址 detail_url
            "content" => $data["content"], // 文章内容（邮件模板html）
            "digest" => $data["description"] // 描述文字
        ];
    }

    /**
     * 设置微信消息应用id
     */
    protected function setAgentId()
    {
        $this->weChatMessage["agentid"] = $this->weChatConfig["agent_id"];
    }

    /**
     * 设置微信消息安全模式
     * @param int $safeMode
     */
    protected function setSafe($safeMode = 1)
    {
        $this->weChatMessage["safe"] = $safeMode;
    }

    /**
     * 设置微信消息接受人员
     * @param array $userData
     */
    protected function setToUser($userData = [])
    {
        if (!empty($userData)) {
            $this->weChatMessage["touser"] = join("|", $userData);
        } else {
            $this->weChatMessage["touser"] = "@all";
        }
    }

    /**
     * 获取微信消息封面图片
     * @param string $mediaPath
     * @return bool|string
     */
    protected function getWeChatCover($mediaPath = '')
    {
        $mediaId = $this->redis->get('wechat_cover_media_id');

        if (!empty($mediaId)) {
            return $mediaId;
        }

        if (!empty($mediaPath)) {
            $res = $this->weChatApp->media->uploadImage($mediaPath);
        } else {
            $res = $this->weChatApp->media->uploadImage($this->weChatConfig["cover_pic"]);
        }

        $this->redis->setex('wechat_cover_media_id', 172800, $res["media_id"]);

        return $res["media_id"];
    }

    /**
     * 发送消息
     * @param $data
     * @return array|bool|\EasyWeChat\Kernel\Support\Collection|object|\Psr\Http\Message\ResponseInterface|string
     */
    public function send($data)
    {
        // 初始化
        $this->init();

        if (!empty($this->weChatApp)) {
            // 设置应用id
            $this->setAgentId();
            // 设置发送用户
            $this->setToUser($data["touser"]);
            // 添加文章
            foreach ($data["articles"] as $article) {
                $this->addArticlesItem($article);
            }

            return $this->weChatApp->message->send($this->weChatMessage);
        }

        return false;
    }
}
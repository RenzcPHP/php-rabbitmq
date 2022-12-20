<?php

namespace Burning\PhpRabbitmq\Objects;

/**
 * 消息发布模式
 * 
 */
class PublishModel extends AbstractObject
{

    /**
     * 默认模式
     */
    const DEFAULT = 'default';

    /**
     * 返回模式
     */
    const RETURN = 'return';

    /**
     * 确认模式
     */
    const CONFIRM = 'confirm';

}
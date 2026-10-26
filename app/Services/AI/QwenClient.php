<?php

namespace App\Services\AI;

class QwenClient extends AbstractOpenAICompatibleClient
{
    protected const API_BASE = 'https://dashscope.aliyuncs.com/compatible-mode/v1/chat/completions';
    protected const DEFAULT_MODEL = 'qwen/qwen3.7-max';
    protected const LABEL = 'Qwen';
}

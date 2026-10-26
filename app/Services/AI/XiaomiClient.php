<?php

namespace App\Services\AI;

class XiaomiClient extends AbstractOpenAICompatibleClient
{
    protected const API_BASE = 'https://api.xiaomi.com/ai/v1/chat/completions';
    protected const DEFAULT_MODEL = 'xiaomi/mimo-v2.5';
    protected const LABEL = 'Xiaomi';
}

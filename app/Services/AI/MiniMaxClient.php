<?php

namespace App\Services\AI;

class MiniMaxClient extends AbstractOpenAICompatibleClient
{
    protected const API_BASE = 'https://api.minimax.io/v1/text/chatcompletion_v2';
    protected const DEFAULT_MODEL = 'minimax/minimax-m3';
    protected const LABEL = 'MiniMax';
}

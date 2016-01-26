<?php

namespace ASM\Bridge;

use ASM\Session;
use Room11\HTTP\HeadersSet;
use Tier\TierApp;


class HTTP
{
    /**
     * @param Session $session
     * @param HeadersSet $headerSet
     */
    function addSessionHeader(Session $session, HeadersSet $headerSet)
    {
        $session->save();

        $headers = $session->getHeaders(\ASM\SessionManager::CACHE_PRIVATE);

        foreach ($headers as $key => $value) {
            $headerSet->addHeader($key, $value);
        }

        return TierApp::PROCESS_CONTINUE;
    }
}
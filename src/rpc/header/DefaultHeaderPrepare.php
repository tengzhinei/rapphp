<?php

namespace rap\rpc;

class DefaultHeaderPrepare implements HeaderPrepare {

    public function header($interface, $method, $data) {
        $header = [];
        $request = request();
        if ($request) {
            $header = $request->header();
            unset($header[ 'host' ]);
            unset($header[ 'content-type' ]);
            unset($header[ 'content-length' ]);
            unset($header[ 'connection' ]);
            unset($header[ 'pragma' ]);
            unset($header[ 'cache-control' ]);
            unset($header[ 'upgrade-insecure-requests' ]);
            unset($header[ 'sec-fetch-mode' ]);
            unset($header[ 'sec-fetch-user' ]);
            unset($header[ 'accept' ]);
            unset($header[ 'sec-fetch-site' ]);
            unset($header[ 'accept-encoding' ]);
            unset($header[ 'accept-language' ]);
            $header[ 'x-session-id' ] = $request->session()->sessionId();
        }
        return $header;
    }
}

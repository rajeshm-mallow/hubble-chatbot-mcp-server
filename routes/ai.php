<?php

use Laravel\Mcp\Facades\Mcp;

Mcp::web('/mcp/hubble-details', \App\Mcp\Servers\HubbleServer::class);

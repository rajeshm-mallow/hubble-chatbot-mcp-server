<?php

namespace App\Mcp\Servers;

use Illuminate\Support\Facades\DB;
use Laravel\Mcp\Server;
use Laravel\Mcp\Server\Prompt;
use Laravel\Mcp\Server\Resource;
use Laravel\Mcp\Server\Tool;

class HubbleServer extends Server
{
    /**
     * The MCP server's name.
     */
    protected string $name = 'Hubble Server';

    /**
     * The MCP server's version.
     */
    protected string $version = '0.0.1';

    /**
     * The MCP server's instructions for the LLM.
     */
    protected string $instructions = <<<'MARKDOWN'
        Instructions describing how to use the server and its features.
    MARKDOWN;

    /**
     * The tools registered with this MCP server.
     *
     * @var array<int, class-string<Tool>>
     */
    protected array $tools = [

    ];

    /**
     * The resources registered with this MCP server.
     *
     * @var array<int, class-string<Resource>>
     */
    protected array $resources = [
        //
    ];

    /**
     * The prompts registered with this MCP server.
     *
     * @var array<int, class-string<Prompt>>
     */
    protected array $prompts = [
        //
    ];
}

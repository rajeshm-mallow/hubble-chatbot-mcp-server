<?php

namespace App\Mcp\Servers;

use App\Mcp\Tools\UserTool;
use App\Mcp\Tools\FindEmployeeTool;
use App\Mcp\Tools\EmployeeLeavesTool;
use App\Mcp\Tools\ListRolesTool;
use App\Mcp\Tools\DepartmentEmployeesTool;
use App\Mcp\Tools\LeaveSummaryTool;
use App\Mcp\Tools\EmployeeHierarchyTool;
use App\Mcp\Tools\TimesheetTool;
use App\Mcp\Tools\ProjectTool;
use App\Mcp\Tools\CardsTool;
use App\Mcp\Tools\TimeoffTool;
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
    protected string $version = '1.0.0';

    /**
     * The MCP server's instructions for the LLM.
     */
    protected string $instructions = <<<'MARKDOWN'
        This is the Hubble MCP Server for comprehensive HR and project management.
        
        ## Core Capabilities:
        
        ### 1. Employee Management & Search
        - **find_employee**: Get current user's employee details and information
        - **user_management**: Get current user's comprehensive account details
        - **department_employees**: Get all employees in current user's department/team
        - **employee_hierarchy**: Get current user's organizational hierarchy and reporting structure
        
        ### 2. Leave & Time-off Management
        - **employee_leaves**: Get current user's leave records and history
        - **leave_summary**: Get leave analytics and trends for current user's team/department
        - **timeoff_data**: Get current user's time-off records and insights
        
        ### 3. Timesheet & Project Tracking
        - **timesheet_data**: Get current user's timesheet entries with detailed analytics
        - **project_data**: Get projects where current user is owner or manager
        - Track work logs, task efforts, and project time allocation
        
        ### 4. Recognition & Rewards
        - **cards_data**: Get recognition cards (Green Cards) related to current user
        - Track card issuance, recipients, and team recognition trends
        
        ### 5. Role & Access Management
        - **list_roles**: List all company roles with details and permissions
        
        ## Key Features:
        - **User-Centric**: All tools automatically use the current user's email from request headers
        - **No Input Parameters**: Simplified interface - tools work based on authenticated user
        - **Comprehensive Analytics**: Statistics, trends, and insights for current user's data
        - **Relationship Management**: Teams, designations, roles, reporting structures
        - **Performance Optimization**: Efficient queries with proper indexing
        
        ## Authentication:
        - All tools require a `user_email` header in the request
        - User details are automatically fetched based on the email
        - Tools return data relevant to the authenticated user
        
        ## Example Use Cases:
        - "Show my employee details"
        - "Get my timesheet entries"
        - "Show my leave history"
        - "List my projects"
        - "Get my team members"
        - "Show my recognition cards"
        - "Get my organizational hierarchy"
        
        Use these tools to provide personalized HR and project management insights for the authenticated user.
    MARKDOWN;

    /**
     * The tools registered with this MCP server.
     *
     * @var array<int, class-string<Tool>>
     */
    protected array $tools = [
        UserTool::class,
        FindEmployeeTool::class,
        EmployeeLeavesTool::class,
        ListRolesTool::class,
        DepartmentEmployeesTool::class,
        LeaveSummaryTool::class,
        EmployeeHierarchyTool::class,
        TimesheetTool::class,
        ProjectTool::class,
        CardsTool::class,
        TimeoffTool::class,
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

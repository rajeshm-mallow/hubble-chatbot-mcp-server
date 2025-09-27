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
        - **find_employee**: Find employees by name, ID, or email with comprehensive details
        - **user_management**: Create, update, delete, activate/deactivate user accounts
        - **department_employees**: Get all employees in a department/team with sorting options
        - **employee_hierarchy**: Get organizational hierarchy and reporting structure
        
        ### 2. Leave & Time-off Management
        - **employee_leaves**: Get leave records for specific employees with filtering
        - **leave_summary**: Comprehensive leave analytics and trends across organization
        - **timeoff_data**: Get time-off records and team insights for short-term absences
        
        ### 3. Timesheet & Project Tracking
        - **timesheet_data**: Get timesheet entries for users, teams, or projects with detailed analytics
        - **project_data**: Get project information with resource allocation and hierarchy
        - Track work logs, task efforts, and project time allocation
        
        ### 4. Recognition & Rewards
        - **cards_data**: Get recognition cards (Green Cards) information and analytics
        - Track card issuance, recipients, and team recognition trends
        
        ### 5. Role & Access Management
        - **list_roles**: List all company roles with details and permissions
        
        ## Key Features:
        - **Multi-criteria Filtering**: Users, teams, projects, dates, status
        - **Date Range Filtering**: Daily, weekly, monthly, yearly views
        - **Comprehensive Analytics**: Statistics, trends, and insights
        - **Relationship Management**: Teams, designations, roles, reporting structures
        - **Performance Optimization**: Efficient queries with proper indexing
        
        ## Example Use Cases:
        - "Find employee John Doe and show his leave history"
        - "Show timesheet entries for Engineering team this week"
        - "List all active projects with resource allocation"
        - "How many green cards were issued this month?"
        - "Who is on leave today in the Sales team?"
        - "Show project hierarchy for Project Alpha"
        - "Get leave summary for Engineering department"
        
        Use these tools to provide comprehensive HR and project management insights.
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

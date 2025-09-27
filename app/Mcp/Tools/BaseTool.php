<?php

namespace App\Mcp\Tools;

use App\Models\User;
use Laravel\Mcp\Server\Tool;

abstract class BaseTool extends Tool
{
    /**
     * Get the current user from the user_email header
     */
    protected function getCurrentUser(): ?User
    {
        $request = request();
        $userEmail = $request->header('user_email');
        
        if (!$userEmail) {
            return null;
        }
        
        return User::where('email', $userEmail)->first();
    }

    /**
     * Get the current user ID from the user_email header
     */
    protected function getCurrentUserId(): ?int
    {
        $user = $this->getCurrentUser();
        return $user ? $user->id : null;
    }

    /**
     * Get the current user or return error if not found
     */
    protected function getCurrentUserOrFail(): User
    {
        $user = $this->getCurrentUser();
        
        if (!$user) {
            throw new \Exception('User not found. Please provide user_email header.');
        }
        
        return $user;
    }
}

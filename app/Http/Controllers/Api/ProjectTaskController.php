<?php

namespace App\Http\Controllers\Api;

use App\Http\Controllers\Controller;
use App\Models\ProjectInvestment;
use App\Models\ProjectTask;
use Illuminate\Http\Request;

class ProjectTaskController extends Controller
{
    /**
     * Get all tasks for a project
     */
    public function index(Request $request, ProjectInvestment $project)
    {
        $query = $project->tasks()->with(['assignee', 'creator']);

        if ($request->filled('status')) {
            $query->where('status', $request->status);
        }

        if ($request->filled('priority')) {
            $query->where('priority', $request->priority);
        }

        if ($request->filled('assigned_to')) {
            $query->where('assigned_to', $request->assigned_to);
        }

        $tasks = $query->orderBy('sort_order')->orderBy('created_at', 'desc')->get();

        return response()->json([
            'tasks' => $tasks,
            'stats' => [
                'total' => $tasks->count(),
                'todo' => $tasks->where('status', ProjectTask::STATUS_TODO)->count(),
                'in_progress' => $tasks->where('status', ProjectTask::STATUS_IN_PROGRESS)->count(),
                'completed' => $tasks->where('status', ProjectTask::STATUS_COMPLETED)->count(),
                'overdue' => $tasks->filter(function ($task) {
                    return $task->due_date && $task->due_date < now() && 
                        !in_array($task->status, [ProjectTask::STATUS_COMPLETED, ProjectTask::STATUS_CANCELLED]);
                })->count(),
            ],
        ]);
    }

    /**
     * Store a new task
     */
    public function store(Request $request, ProjectInvestment $project)
    {
        $validated = $request->validate([
            'title' => 'required|string|max:255',
            'description' => 'nullable|string',
            'status' => 'in:todo,in_progress,completed,cancelled',
            'priority' => 'in:low,medium,high,urgent',
            'due_date' => 'nullable|date',
            'assigned_to' => 'nullable|exists:users,id',
            'sort_order' => 'nullable|integer',
        ]);

        $validated['project_investment_id'] = $project->id;
        $validated['created_by'] = auth()->id();
        $validated['status'] = $validated['status'] ?? ProjectTask::STATUS_TODO;
        $validated['priority'] = $validated['priority'] ?? ProjectTask::PRIORITY_MEDIUM;

        $task = ProjectTask::create($validated);
        $task->load(['assignee', 'creator']);

        return response()->json($task, 201);
    }

    /**
     * Update a task
     */
    public function update(Request $request, ProjectTask $task)
    {
        $validated = $request->validate([
            'title' => 'required|string|max:255',
            'description' => 'nullable|string',
            'status' => 'in:todo,in_progress,completed,cancelled',
            'priority' => 'in:low,medium,high,urgent',
            'due_date' => 'nullable|date',
            'assigned_to' => 'nullable|exists:users,id',
            'progress' => 'nullable|integer|min:0|max:100',
            'sort_order' => 'nullable|integer',
        ]);

        // Auto-update completed date if status changed to completed
        if (isset($validated['status']) && $validated['status'] === ProjectTask::STATUS_COMPLETED && $task->status !== ProjectTask::STATUS_COMPLETED) {
            $validated['completed_date'] = now();
            $validated['progress'] = 100;
        }

        // Reset completed date if status changed from completed
        if (isset($validated['status']) && $validated['status'] !== ProjectTask::STATUS_COMPLETED && $task->status === ProjectTask::STATUS_COMPLETED) {
            $validated['completed_date'] = null;
        }

        $task->update($validated);
        $task->load(['assignee', 'creator']);

        return response()->json($task);
    }

    /**
     * Delete a task
     */
    public function destroy(ProjectTask $task)
    {
        $task->delete();

        return response()->json(['message' => 'Task deleted successfully']);
    }

    /**
     * Update task status
     */
    public function updateStatus(Request $request, ProjectTask $task)
    {
        $validated = $request->validate([
            'status' => 'required|in:todo,in_progress,completed,cancelled',
        ]);

        if ($validated['status'] === ProjectTask::STATUS_COMPLETED) {
            $task->markAsCompleted();
        } else {
            $task->status = $validated['status'];
            if ($validated['status'] === ProjectTask::STATUS_IN_PROGRESS && $task->progress === 0) {
                $task->progress = 10; // Start with 10% progress
            }
            $task->save();
        }

        $task->load(['assignee', 'creator']);

        return response()->json($task);
    }

    /**
     * Reorder tasks
     */
    public function reorder(Request $request, ProjectInvestment $project)
    {
        $validated = $request->validate([
            'tasks' => 'required|array',
            'tasks.*.id' => 'required|exists:project_tasks,id',
            'tasks.*.sort_order' => 'required|integer',
        ]);

        foreach ($validated['tasks'] as $taskData) {
            ProjectTask::where('id', $taskData['id'])
                ->where('project_investment_id', $project->id)
                ->update(['sort_order' => $taskData['sort_order']]);
        }

        return response()->json(['message' => 'Tasks reordered successfully']);
    }
}

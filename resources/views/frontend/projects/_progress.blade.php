<div class="tc-progress" role="progressbar" aria-label="Progress of {{ $project->name }}"
     aria-valuenow="{{ $project->progress }}" aria-valuemin="0" aria-valuemax="100">
    <span class="tc-progress-bar" style="width: {{ $project->progress }}%"></span>
</div>

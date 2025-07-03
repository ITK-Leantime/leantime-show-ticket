@extends($layout)
@section('content')
    <div class="maincontent">
        <div class="maincontentinner">
            <div class="ticket">
                <div class="ticket-header">
                    <div>
                        <div id="notification" class="ticket-notification">
                            {{ __('showTicket.saving') }}
                            <i id="spinner" class="spinner fa-solid fa-spinner fa-spin-pulse"></i>
                        </div>
                    </div>
                    <div class="ticket-buttons">
                        <button type="button" class="button" id="copy-url-button">
                            <span class="sr-only">
                                {{ __('showTicket.copy-current-url') }}
                            </span>
                            <i class="fa fa-link"></i>
                        </button>
                        <button type="button" class="button" disabled id="save-ticket-button">
                            <span class="sr-only">
                                {{ __('showTicket.save-ticket') }}
                            </span>
                            <i class="fa-solid fa-floppy-disk"></i>
                        </button>
                    </div>
                </div>
                <main project-id="{{ $projectId }}" class="ticket-content ticket-content-create">
                    <h1>{{ __('showTicket.create-new-ticket') }}</h1>
                    <form method="POST">
                        <div class="label-input-container">
                            <label for="project-id">{{ __('showTicket.projects-label') }}</label>
                            <select id="project-id" name="projectId" class="select" {{ $projectId ? 'disabled' : '' }}>
                                <option value="">
                                    {{ __('showTicket.no-project-selected') }}
                                </option>
                                @foreach ($projects as $key => $project)
                                    <option value="{{ $project['id'] }}"
                                        {{ $projectId == $project['id'] ? 'selected' : '' }}>
                                        {{ $project['name'] }}
                                    </option>
                                @endforeach
                            </select>
                        </div>
                    </form>
                    @if ($projectId !== '')
                        <input type="text" class="input full-width" id="headline-input" />
                        <label class="sr-only" for="description-input">
                            {{ __('showTicket.description-label') }}
                        </label>
                        {{-- The below is a wrapper to show the success/error animation, it could be improved --}}
                        <div class="rich-text-success" id="rich-text-success">
                            {{-- this textarea will be a rich text editor, init in create-ticket.js --}}
                            <textarea type="text" class="textarea" id="description-input"></textarea>
                        </div>
                        <div class="label-input-container">
                            <label for="status-select">{{ __('showTicket.status-label') }}</label>
                            <select id="status-select" class="select">
                                @foreach ($statusLabels as $key => $statusLabel)
                                    <option value="{{ $key }}">
                                        {{ $statusLabel['name'] }}
                                    </option>
                                @endforeach
                            </select>
                        </div>
                        <div class="label-input-container">
                            <label for="plan-hours-input">{{ __('showTicket.plan-hours-label') }}</label>
                            <input type="text" class="input" id="plan-hours-input" />
                        </div>
                        <div class="label-input-container">
                            <label for="priority-select">{{ __('showTicket.priority-label') }}</label>
                            <select id="priority-select" class="select">
                                <option value="0">
                                    {{ __('showTicket.no-priority-set-option') }}
                                </option>
                                @foreach ($priorityLabels as $key => $priorityLabel)
                                    <option value="{{ $key }}">
                                        {{ $priorityLabel }}
                                    </option>
                                @endforeach
                            </select>
                        </div>
                        <div class="label-input-container">
                            <label for="user-select">{{ __('showTicket.editor-label') }}</label>
                            <select class="select" id="user-select">
                                @foreach ($allUsers as $user)
                                    <option value={{ $user['id'] }}>
                                        {{ $user['firstname'] }}
                                        {{ $user['lastname'] }}
                                    </option>
                                @endforeach
                            </select>
                        </div>
                        <div class="label-input-container">
                            <label for="date-to-finish-input">{{ __('showTicket.date-to-finish-label') }}</label>
                            <input type="date" type="text" class="input" id="date-to-finish-input" />
                        </div>
                        <div class="label-input-container">
                            <label for="sprint-select">{{ __('showTicket.sprint-label') }}</label>
                            <select class="select" id="sprint-select">
                                <option value="0">{{ __('showTicket.no-sprint-set-option') }}</option>
                                @foreach ($sprints as $sprint)
                                    <option value={{ $sprint->id }}>
                                        {{ $sprint->name }}
                                    </option>
                                @endforeach
                            </select>
                        </div>
                        <div class="label-input-container" id="tags-container">
                            <label for="tags-select">{{ __('showTicket.tags-label') }}</label>
                            <select id="tags-select" class="input"></select>
                            <div id="skeleton-input" class="skeleton-input">{{ __('showTicket.loading-tags') }}</div>
                        </div>
                        <div class="label-input-container">
                            <label for="milestone-select">{{ __('showTicket.milestone-label') }}</label>
                            <select class="select" id="milestone-select">
                                <option value="0">{{ __('showTicket.no-milestone-set-option') }}</option>
                                @foreach ($milestones as $milestone)
                                    <option value={{ $milestone->id }}>
                                        {{ $milestone->headline }}
                                    </option>
                                @endforeach
                            </select>
                        </div>
                    @endif
            </div>
            </main>
        </div>
    </div>
@endsection

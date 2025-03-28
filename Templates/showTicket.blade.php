@extends($layout)
@section('content')
    <div class="show-ticket">
    @if (!isset($ticket))
        <main class="show-ticket-content">
        <h1>
        {!! sprintf(
                __('showTicket.no-ticket'),
                $ticketIdFromUrl,
            ) !!}
            </h1>
        </main>
    @endif
            @if (isset($ticket))
        <div class="show-ticket-header">
            <div class="show-ticket-notification">
                {{ __('showTicket.auto-save-on') }}
                <i id="spinner" class="spinner fa-solid fa-spinner fa-spin-pulse"></i>
            </div>
            <div class="show-ticket-buttons">
                <button type="button" class="button" id="copy-url-button" onclick='copyCurrentUrl()'>
                    <span class="sr-only">
                        {{ __('showTicket.copy-current-url') }}
                    </span>
                    <i class="fa fa-link"></i>
                </button>

                <button id="delete-ticket" class="button animate-button" type="button">
                    <span class="sr-only">{{ __('showTicket.delete-ticket') }}</span>
                    <i class="fa fa-trash"></i>
                </button>
            </div>
        </div>
            <main id="{{ $ticket->id }}" class="show-ticket-content">
                <h1>{{ $ticket->projectName }}: {{ $ticket->id }}</h1>
                <label class="sr-only" for="headline-input">
                    {{ __('showTicket.headline-label') }}
                </label>
                <input defaultValue="{{ $ticket->headline }}" type="text" class="input" id="headline-input"
                    value='{{ $ticket->headline }}' />
                <label class="sr-only" for="description-input">
                    {{ __('showTicket.description-label') }}
                </label>
                <textarea defaultValue="{{ $ticket->description }}" type="text" class="textarea"
                    id="description-input">{{ $ticket->description }}</textarea>
                <div class="label-input-container">
                    <label for="status-select">{{ __('showTicket.status-label') }}</label>
                    <select defaultValue="{{ $ticket->status }}" id="status-select" class="select">
                        @foreach ($statusLabels as $key => $statusLabel)
                            <option value="{{ $key }}" {{ $ticket->status == $key ? 'selected' : '' }}>
                                {{ $statusLabel['name'] }}
                            </option>
                        @endforeach
                    </select>
                </div>
                <div class="label-input-container">
                    <label for="status-select">{{ __('showTicket.plan-hours-label') }}</label>
                    <input defaultValue="{{ $ticket->planHours }}" type="text" class="input" id="plan-hours-input"
                        value='{{ $ticket->planHours }}' />
                </div>
                <div class="label-input-container">
                    <label for="priority-select">{{ __('showTicket.priority-label') }}</label>
                    <select defaultValue="{{ $ticket->priority }}" id="priority-select" class="select">
                        <option value="0" {{ $ticket->priority == 0 ? 'selected' : '' }}>
                            {{ __('showTicket.no-priority-set-option') }}
                        </option>
                        @foreach ($priorityLabels as $key => $priorityLabel)
                            <option value="{{ $key }}" {{ $ticket->priority == $key ? 'selected' : '' }}>
                                {{ $priorityLabel }}
                            </option>
                        @endforeach
                    </select>
                </div>
                <div class="label-input-container">
                    <label for="user-select">{{ __('showTicket.editor-label') }}</label>
                    <select defaultValue="{{ $ticket->editorId }}" class="select" id="user-select">
                        @foreach ($allUsers as $user)
                            <option value={{ $user['id'] }} {{ $ticket->editorId == $user['id'] ? 'selected' : '' }}>
                                {{ $user['firstname'] }}
                                {{ $user['lastname'] }}
                            </option>
                        @endforeach
                    </select>
                </div>
                @php
                    $dateToFinish = $ticket->dateToFinish == '0000-00-00 00:00:00' ? '' : strtok($ticket->dateToFinish, ' ');
                @endphp
                <div class="label-input-container">
                    <label for="status-date">{{ __('showTicket.date-to-finish-label') }}</label>
                    <input type="date" defaultValue="{{ $ticket->dateToFinish }}" type="text" class="input"
                        id="date-to-finish-input" value='{{ $dateToFinish }}' />
                </div>
                <div class="label-input-container">
                    <label for="sprint-select">{{ __('showTicket.sprint-label') }}</label>
                    <select defaultValue="{{ $ticket->sprint }}" class="select" id="sprint-select">
                        <option value="0">{{ __('showTicket.no-sprint-set-option') }}</option>
                        @foreach ($sprints as $sprint)
                            <option value={{ $sprint->id }} {{ $ticket->sprint == $sprint->id ? 'selected' : '' }}>
                                {{ $sprint->name }}
                            </option>
                        @endforeach
                    </select>
                </div>
                <div class="label-input-container">
                    <label for="milestone-select">{{ __('showTicket.milestone-label') }}</label>
                    <select defaultValue="{{ $ticket->milestoneid }}" class="select" id="milestone-select">
                        <option value="0">{{ __('showTicket.no-milestone-set-option') }}</option>
                        @foreach ($milestones as $milestone)
                            <option value={{ $milestone->id }}
                                {{ $ticket->milestoneid == $milestone->id ? 'selected' : '' }}>
                                {{ $milestone->headline }}
                            </option>
                        @endforeach
                    </select>
                </div>
                <div class="label-input-container">
                    <label for="related-tickets-select">{{ __('showTicket.related-tickets-label') }}</label>
                    <select defaultValue="{{ $ticket->dependingTicketId }}" class="select" id="related-tickets-select">
                        <option value="0">{{ __('showTicket.no-related-tickets-set-option') }}</option>
                        @foreach ($relatedTickets as $relatedTicket)
                            <option value={{ $relatedTicket->id }}
                                {{ $ticket->dependingTicketId == $relatedTicket->id ? 'selected' : '' }}>
                                {{ $relatedTicket->headline }}
                            </option>
                        @endforeach
                    </select>
                </div>
                <div class="label-input-container">
                    <label for="status-select">{{ __('showTicket.tags-label') }}</label>
                    <input id="tags-input" type="text" value="{{ $ticket->tags }}" type="text" class="input">
                </div>
                <div class="label-input-container">
                    <label for="tags-list" class="equal-space">{{ __('showTicket.existing-tags-label') }}</label>
                    <p id="tags-list" class="equal-space">
                        @foreach ($tags as $index => $tag)
                            {{ $tag }}@if ($index < count($tags) - 2),
                            @elseif($index == count($tags) - 2)
                                {{ __('showTicket.tags-and-label') }}
                            @else
                            @endif
                        @endforeach
                    </p>
                </div>
                @if (count($files) > 0)
                    <div class="label-input-container">
                        <label for="files-list" class="equal-space">{{ __('showTicket.files-label') }}</label>
                        <div class="equal-space" id="files-list">
                            @foreach ($files as $file)
                                <a class="file-link"
                                    href="<?= BASE_URL ?>/files/get?module={{ $file['module'] }}&encName={{ $file['encName'] }}&ext={{ $file['extension'] }}&realName={{ $file['realName'] }}">
                                    @if ($file['extension'] == 'pdf')
                                        <i class="fa-solid fa-file-pdf"></i>
                                    @elseif( in_array($file['extension'], ['png','jpg','jpeg','gif']))
                                        <i class="fa-solid fa-file-image"></i>
                                    @elseif( in_array($file['extension'], ['mp4','mov']))
                                        <i class="fa-solid fa-film"></i>
                                    @else
                                        <i class="fa-solid fa-file"></i>
                                    @endif
                                    <span class="filename">{{ $file['realName'] }}.{{ $file['extension'] }}</span>
                                </a>
                            @endforeach
                        </div>
                    </div>
                @endif
            </main>
        @endif
    </div>
@endsection

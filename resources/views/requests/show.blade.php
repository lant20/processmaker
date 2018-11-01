@extends('layouts.layout')

@section('title')
    {{__('Request Detail')}}
@endsection

@section('sidebar')
    @include('layouts.sidebar', ['sidebar' => Menu::get('sidebar_request')])
@endsection

@section('content')
    <div id="request" class="container">

        <h1>{{$request->name}} # {{$request->getKey()}}</h1>
        <div class="row">
            <div class="col-8">

                <div class="container-fluid">
                    <ul class="nav nav-tabs" id="requestTab" role="tablist">
                        <li class="nav-item" v-if="status !== 'Completed'">
                            <a class="nav-link active" id="pending-tab" data-toggle="tab" href="#pending" role="tab"
                               aria-controls="pending" aria-selected="true">{{__('Pending Tasks')}}</a>
                        </li>
                        <li class="nav-item">
                            <a  id="summary-tab" data-toggle="tab" href="#summary" role="tab"
                               aria-controls="summary" aria-selected="false" v-bind:class="{ 'nav-link':true, active: (status === 'Completed') }">{{__('Request Summary')}}</a>
                        </li>
                        <li class="nav-item">
                            <a class="nav-link" id="completed-tab" data-toggle="tab" href="#completed" role="tab"
                               aria-controls="completed" aria-selected="false">{{__('Completed')}}</a>
                        </li>
                    </ul>
                    <div class="tab-content" id="requestTabContent">
                        <div class="tab-pane fade show active" id="pending" role="tabpanel"
                             aria-labelledby="pending-tab" v-if="status !== 'Completed'">
                            <request-detail ref="pending" :process-request-id="requestId" status="ACTIVE"></request-detail>
                        </div>
                        <div v-bind:class="{ 'tab-pane':true, active: (status === 'Completed') }" id="summary" role="tabpanel" aria-labelledby="summary-tab">
                            <template v-if="showSummary">
                                <table class="vuetable table table-hover">
                                    <thead>
                                    <tr>
                                        <th scope="col">{{ __('Key') }}</th>
                                        <th scope="col">{{ __('Value') }}</th>
                                    </tr>
                                    </thead>
                                    <tbody>
                                    <tr v-for="item in summary">
                                        <td>@{{item.key}}</td>
                                        <td>@{{item.value}}</td>
                                    </tr>
                                    </tbody>
                                </table>
                            </template>
                            <template v-else>
                                <div class="card m-3">
                                    <div class="card-header">
                                        <h5>
                                            {{ __('Request In Progress') }}
                                        </h5>
                                    </div>

                                    <div class="card-body">
                                        <p class="card-text">
                                            This request is currently in progress.
                                            This screen will be populated once the request is completed.
                                        </p>
                                    </div>
                                </div>
                            </template>
                        </div>
                        <div class="tab-pane fade" id="completed" role="tabpanel" aria-labelledby="completed-tab">
                            <request-detail ref="completed" :process-request-id="requestId" status="CLOSED"></request-detail>
                        </div>
                    </div>
                </div>
            </div>
            <div class="col-4">
                <template v-if="statusLabel">
                    <div class="card">
                        <div :class="classStatusCard">
                            <h4 style="margin:0; padding:0; line-height:1">@{{ statusLabel }}</h4>
                        </div>
                        <ul class="list-group list-group-flush w-100">
                            <li class="list-group-item">
                                <h5>{{__('Requested By')}}</h5>
                                <avatar-image size="32" class="d-inline-flex pull-left align-items-center"
                                              :input-data="requestBy" display-name="true"></avatar-image>
                            </li>
                            <li class="list-group-item">
                                <h5>{{__('Participants')}}</h5>
                                <avatar-image size="32" class="d-inline-flex pull-left align-items-center"
                                              :input-data="participants"  hide-name="true"></avatar-image>
                            </li>
                            <li class="list-group-item">
                                <i class="far fa-calendar-alt"></i>
                                <small>@{{ labelDate }} @{{ moment(statusDate).fromNow() }}</small>
                                <br>
                                @{{moment(statusDate).format('MM/DD/YYYY HH:MM')}}
                            </li>
                        </ul>
                    </div>
                </template>
            </div>

        </div>
    </div>

@endsection

@section('js')
    <script src="{{mix('js/requests/show.js')}}"></script>
    <script>
        new Vue({
            el: "#request",
            data() {
                return {
                    requestId: @json($request->getKey()),
                    request: @json($request),
                    refreshTasks: 0,
                    status: ''
                };
            },
            computed: {
                /**
                 * Get the list of participants in the request.
                 *
                 */
                participants() {
                    /*const participants = [];
                    this.request.participants.forEach(user => {
                        user.src = user.avatar;
                        user.title = user.fullname;
                        user.name = '';
                        user.initials = user.firstname.match(/./u)[0] + user.lastname.match(/./u)[0];
                        participants.push(user);
                    });*/
                    return this.request.participants;
                },
                /**
                 * Request Summary - that is blank place holder if there are in progress tasks,
                 * if the request is completed it will show key value pairs.
                 *
                 */
                showSummary() {
                    return this.request.status === 'COMPLETED';
                },
                /**
                 * Get the summary of the Request.
                 *
                 */
                summary() {
                    return this.request.summary;
                },
                classStatusCard() {
                    let header = {
                        "ACTIVE": "bg-success",
                        "COMPLETED": "bg-secondary",
                        "ERROR": "bg-danger"
                    };
                    return 'card-header text-capitalize text-white ' + header[this.request.status.toUpperCase()];
                },
                statusLabel() {
                    let label = {
                        "ACTIVE": 'In Progress',
                        "COMPLETED": 'Completed',
                        "ERROR": 'Error'
                    };

                    if(this.request.status.toUpperCase() === 'COMPLETED'){
                      this.status = 'Completed'
                    }
                    return label[this.request.status.toUpperCase()];
                },
                labelDate() {
                    let label = {
                        "ACTIVE": 'Created',
                        "COMPLETED": 'Completed On',
                        "ERROR": 'Failed On'
                    };
                    return label[this.request.status.toUpperCase()];
                },
                statusDate() {
                    let status = {
                        "ACTIVE": this.request.created_at,
                        "COMPLETED": this.request.completed_at,
                        "ERROR": this.request.updated_at
                    };
                    return status[this.request.status.toUpperCase()];
                },
                requestBy() {
                    return [this.request.user]
                },
            },
            methods: {
                /**
                 * Refresh the Request details.
                 *
                 */
                refreshRequest() {
                    this.$refs.pending.fetch();
                    this.$refs.completed.fetch();
                    ProcessMaker.apiClient.get(`requests/${this.requestId}`, {
                        params: {
                            include: 'participants,user,summary'
                        }
                    })
                        .then((response) => {
                            for (let attribute in response.data) {
                                this.updateModel(this.request, attribute, response.data[attribute]);
                            }
                            this.refreshTasks++;
                        });
                },
                /**
                 * Update a model property.
                 *
                 */
                updateModel(obj, prop, value, defaultValue) {
                    const descriptor = Object.getOwnPropertyDescriptor(obj, prop);
                    value = value !== undefined ? value : (descriptor ? obj[prop] : defaultValue);
                    if (descriptor && !(descriptor.get instanceof Function)) {
                        delete obj[prop];
                        Vue.set(obj, prop, value);
                    } else if (descriptor && obj[prop] !== value) {
                        Vue.set(obj, prop, value);
                    }
                },
                /**
                 * Listen for Request updates.
                 *
                 */
                listenRequestUpdates() {
                    let userId = document.head.querySelector('meta[name="user-id"]').content;
                    Echo.private(`ProcessMaker.Models.User.${userId}`)
                        .notification((token) => {
                            if (token.request_id === this.requestId) {
                                this.refreshRequest();
                            }
                        });
                }
            },
            mounted() {
                this.listenRequestUpdates();
            },
        });
    </script>
@endsection
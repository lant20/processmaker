@extends('layouts.layout')

@section('meta')
    <meta name="request-id" content="{{ $task->processRequest->id }}">
@endsection

@section('title')
    {{__('Edit Task')}}
@endsection

@section('sidebar')
    @include('layouts.sidebar', ['sidebar'=> Menu::get('sidebar_task')])
@endsection

@section('breadcrumbs')
    @include('shared.breadcrumbs', ['routes' => [
        __('Tasks') => route('tasks.index'),
        function() use ($task) {
            if ($task->advanceStatus == 'completed') {
                return ['Completed Tasks', route('tasks.index', ['status' => 'CLOSED'])];
            }
            return ['To Do Tasks', route('tasks.index')];
        },
        $task->processRequest->name =>
            Auth::user()->can('view', $task->processRequest) ? route('requests.show', ['request' => $task->processRequest->id]) : null,
        '@{{taskTitle}}' => null,
      ], 'attributes' => 'v-cloak'])
@endsection
@section('content')
    <div v-cloak id="task" class="container-fluid px-3">
        <div class="d-flex flex-column flex-md-row">
            <div class="flex-grow-1">
                <div v-if="isSelfService" class="alert alert-primary" role="alert">
                    <button type="button" class="btn btn-primary" @click="claimTask">{{__('Claim Task')}}</button>
                    {{__('This task is unassigned, click Claim Task to assign yourself.')}}
                </div>
                <div v-else class="container-fluid h-100 d-flex flex-column">
                    @can('editData', $task->processRequest)
                        <ul v-if="task.process_request.status === 'ACTIVE'" id="tabHeader" role="tablist" class="nav nav-tabs">
                            <li class="nav-item"><a id="pending-tab" data-toggle="tab" href="#tab-form" role="tab"
                                                    aria-controls="tab-form" aria-selected="true"
                                                    class="nav-link active">{{__('Form')}}</a></li>
                            <li class="nav-item"><a id="summary-tab" data-toggle="tab" href="#tab-data" role="tab"
                                                    aria-controls="tab-data" aria-selected="false"
                                                    @click="resizeMonaco"
                                                    class="nav-link">{{__('Data')}}</a></li>
                        </ul>
                    @endcan
                    <div id="tabContent" class="tab-content flex-grow-1">
                        <div id="tab-form" role="tabpanel" aria-labelledby="tab-form" class="tab-pane active show h-100">
                          @can('update', $task)
                            <task :task="task" :csrf-token="'{{ csrf_token() }}'"></task>
                          @endcan
                            @can('view-comments')
                              <div v-if="taskHasComments">
                                <timeline :commentable_id="task.id"
                                          commentable_type="ProcessMaker\Models\ProcessRequestToken"
                                          :reactions="taskHasComments.reactions"
                                          :voting="taskHasComments.voting"
                                          :edit="taskHasComments.edit_comments"
                                          :remove="taskHasComments.remove_comments"
                                          :adding="taskHasComments.comments && userHasAccessToTask"
                                          :readonly="task.status === 'CLOSED'"
                                          />
                              </div>
                            @endcan 
                        </div>
                        @can('editData', $task->processRequest)
                            <div v-if="task.process_request.status === 'ACTIVE'" id="tab-data" role="tabpanel" aria-labelledby="tab-data" class="card card-body border-top-0 tab-pane p-3">
                                @include('tasks.editdata')
                            </div>
                        @endcan
                    </div>
                </div>
            </div>
            <div class="ml-md-3 mt-3 mt-md-0">
                <div class="card">
                    <div :class="statusCard">
                        <h4 style="margin:0; padding:0; line-height:1">@{{$t(task.advanceStatus)}}</h4>
                    </div>
                    <ul class="list-group list-group-flush w-100">
                        <li v-if="dateDueAt" class="list-group-item" v-if="showDueAtDates">
                            <i class='far fa-calendar-alt'></i>
                            <small> @{{$t(dueLabel)}} @{{ moment(dateDueAt).fromNow() }}
                            </small>
                            <br>
                            @{{ moment(dateDueAt).format() }}
                        </li>


                        <li class="list-group-item" v-if="!showDueAtDates">
                            <i class='far fa-calendar-alt'></i>
                            <small> @{{$t(dueLabel)}} @{{ moment().to(moment(completedAt)) }}
                            </small>
                            <br>
                            @{{ moment(completedAt).format() }}
                        </li>

                        <li class="list-group-item">
                            <h5>{{__('Assigned To')}}</h5>
                            <avatar-image v-if="task.user" size="32" class="d-inline-flex pull-left align-items-center"
                                          :input-data="task.user"></avatar-image>
                          <div v-if="task.definition.allowReassignment === 'true'">
                            <br>
                            <span>
                                <button v-if="task.advanceStatus === 'open'" type="button" class="btn btn-outline-secondary btn-block"
                                        @click="show">
                                    <i class="fas fa-user-friends"></i> {{__('Reassign')}}
                                </button>
                              <b-modal v-model="showReassignment" size="md" centered title="{{__('Reassign to')}}"
                                      @hide="cancelReassign"
                                      header-close-content="&times;"
                                      v-cloak>
                                <div class="form-group">
                                    {!!Form::label('user', __('User'))!!}
                                    <multiselect v-model="selectedUser"
                                                  placeholder="{{__('Select the user to reassign to the task')}}"
                                                  :options="usersList"
                                                  :multiple="false"
                                                  track-by="fullname"
                                                  :show-labels="false"
                                                  :searchable="false"
                                                  :internal-search="false"
                                                  @search-change="loadUsers"
                                                  label="fullname">
                                          <template slot="noResult">
                                            {{ __('No elements found. Consider changing the search query.') }}
                                        </template>
                                        <template slot="noOptions">
                                            {{ __('No Data Available') }}
                                        </template>
                                        <template slot="tag" slot-scope="props">
                                            <span class="multiselect__tag  d-flex align-items-center"
                                                  style="width:max-content;">
                                                <span class="option__desc mr-1">
                                                    <span class="option__title">@{{ props.option.fullname }}</span>
                                                </span>
                                                <i aria-hidden="true" tabindex="1"
                                                    @click="props.remove(props.option)"
                                                    class="multiselect__tag-icon"></i>
                                            </span>
                                        </template>

                                        <template slot="option" slot-scope="props">
                                            <div class="option__desc d-flex align-items-center">
                                                <span class="option__title mr-1">@{{ props.option.fullname }}</span>
                                            </div>
                                        </template>
                                    </multiselect>
                                </div>
                                <div slot="modal-footer">
                                    <button type="button" class="btn btn-outline-secondary" @click="cancelReassign">
                                        {{__('Cancel')}}
                                    </button>
                                    <button type="button" class="btn btn-secondary ml-2" @click="reassignUser"
                                            :disabled="disabled">
                                        {{__('Reassign')}}
                                    </button>
                                </div>
                              </b-modal>
                            </span>
                          </div>
                        </li>
                        <li class="list-group-item">
                            <i class="far fa-calendar-alt"></i>
                            <small> {{__('Assigned') }} @{{ moment(createdAt).fromNow() }}</small>
                            <br>
                            @{{ moment(createdAt).format() }}
                        </li>
                        <li class="list-group-item">
                            <h5>{{__('Request')}}</h5>
                            <a href="{{route('requests.show', [$task->process_request_id, 'skipInterstitial' => '1'])}}">
                                #{{$task->process_request_id}} {{$task->process->name}}
                            </a>
                            <br><br>
                            <h5>{{__('Requested By')}}</h5>
                            <avatar-image v-if="task.requestor" size="32"
                                          class="d-inline-flex pull-left align-items-center"
                                          :input-data="task.requestor"></avatar-image>
                            <p v-if="!task.requestor">{{__('Web Entry')}}</p>
                        </li>
                    </ul>
                </div>
            </div>
        </div>
    </div>
@endsection

@section('js')
  <script>
    window.ProcessMaker.EventBus.$on("screen-renderer-init", (screen) => {
      if (screen.watchers_config) {
        screen.watchers_config.api.execute = @json(route('api.scripts.execute', ['script_id' => 'script_id', 'script_key' => 'script_key']));
        screen.watchers_config.api.execution = @json(route('api.scripts.execution', ['key' => 'execution_key']));
      } else {
        console.warn('Screen builder version does not have watchers');
      }
    });

    window.PM4ConfigOverrides = {
      requestFiles: @json($files),
      getScreenEndpoint: 'tasks/{{ $task->id }}/screens',
    };
  </script>
    @foreach($manager->getScripts() as $script)
        <script src="{{$script}}"></script>
    @endforeach
    <script src="{{mix('js/tasks/show.js')}}"></script>
    <script>
      const main = new Vue({
        el: "#task",
        data: {
          //Edit data
          fieldsToUpdate: [],
          jsonData: "",
          monacoLargeOptions: {
            automaticLayout: true,
          },
          showJSONEditor: false,

          // Reassignment
          selected: null,
          selectedIndex: -1,
          usersList: [],
          filter: "",
          showReassignment: false,

          task: @json($task->toArray()),
          userHasAccessToTask: {{ Auth::user()->can('update', $task) ? "true": "false" }},
          statusCard: "card-header text-capitalize text-white bg-success",
          selectedUser: [],
          hasErrors: false,
          redirectInProcess: false,
        },
        watch: {
          task: {
            deep: true,
            handler(task) {
              window.ProcessMaker.breadcrumbs.taskTitle = task.element_name;
            }
          },
          showReassignment (show) {
            show ? this.loadUsers() : null;
          }
        },
        computed: {
          taskHasComments() {
            const commentsPackage = 'comment-editor' in Vue.options.components;
            let config = {};
            if (commentsPackage && this.task.definition && this.task.definition.config) {
              config = JSON.parse(this.task.definition.config);
            }
            return config;
          },
          dueLabel() {
            const dueLabels = {
              'open': 'Due',
              'completed': 'Completed',
              'overdue': 'Due',
            };
            return dueLabels[this.task.advanceStatus] || '';
          },
          isSelfService() {
            return this.task.process_request.status === 'ACTIVE' && this.task.is_self_service;
          },
          dateDueAt () {
            return this.task.due_at;
          },
          createdAt () {
            return this.task.created_at;
          },
          completedAt () {
            return this.task.completed_at;
          },
          showDueAtDates () {
            return this.task.status !== "CLOSED";
          },
          disabled () {
            return this.selectedUser ? this.selectedUser.length === 0 : true;
          },
          styleDataMonaco () {
            let height = window.innerHeight * 0.55;
            return "height: " + height + "px; border:1px solid gray;";
          }
        },
        methods: {
          claimTask() {
            ProcessMaker.apiClient
              .put("tasks/" + this.task.id, {
                user_id: window.ProcessMaker.user.id,
                is_self_service: 0,
              })
              .then(response => {
                this.reload();
              });
          },
          // Data editor
          updateRequestData () {
            const data = JSON.parse(this.jsonData);
            ProcessMaker.apiClient
              .put("requests/" + this.task.process_request_id, {
                data: data,
                task_element_id: this.task.element_id,
              })
              .then(response => {
                this.fieldsToUpdate.splice(0);
                ProcessMaker.alert("{{__('The request data was saved.')}}", "success");
              });
          },
          saveJsonData () {
            try {
              const value = JSON.parse(this.jsonData);
              this.updateRequestData();
            } catch (e) {
              // Invalid data
            }
          },
          editJsonData () {
            this.jsonData = JSON.stringify(this.task.request_data, null, 4);
          },
          // Reassign methods
          show () {
            this.showReassignment = true;
          },
          cancelReassign () {
            this.showReassignment = false;
            this.selectedUser = [];
          },
          reassignUser () {
            if (this.selectedUser) {
              ProcessMaker.apiClient
                .put("tasks/" + this.task.id, {
                  user_id: this.selectedUser.id
                })
                .then(response => {
                  this.showReassignment = false;
                  this.selectedUser = [];
                  this.redirect('/tasks');
                });
            }
          },
          redirect(to) {
            if (this.redirectInProcess) {
              return;
            }
            this.redirectInProcess = true;
            window.location.href = to;
          },
          loadUsers (filter) {
            if (this.redirectInProcess) {
              return;
            }

            filter = typeof filter === "string" ? "?filter=" + filter + "&" : "?";
            ProcessMaker.apiClient
              .get(
                "tasks/" + this.task.id + filter, {
                  params: {
                    include: "assignableUsers"
                  }
                }
              )
              .then(response => {
                this.usersList = response.data.assignable_users.filter(u => u.id !== this.task.user_id);
              });
          },
          assignedUserAvatar (user) {
            return [{
              src: user.avatar,
              name: user.fullname
            }];
          },
          resizeMonaco () {
            let editor = this.$refs.monaco.getMonaco();
            editor.layout({height: window.innerHeight * 0.65});
          },
          prepareData() {
              this.updateRequestData = debounce(this.updateRequestData, 1000);
              this.editJsonData();
          },
        },
        mounted() {
          this.prepareData();
          this.$on('updateTask', (val) => {
              this.$set(this, 'task', val);
          });
        }
      });
      window.ProcessMaker.breadcrumbs.taskTitle = @json($task->element_name)
    </script>
@endsection

@section('css')
    <style>
        .inline-input {
            margin-right: 6px;
        }

        .inline-button {
            background-color: rgb(109, 124, 136);
            font-weight: 100;
        }

        .input-and-select {
            width: 212px;
        }

        .multiselect__element span img {
            border-radius: 50%;
            height: 20px;
        }

        .multiselect__tags-wrap {
            display: flex !important;
        }

        .multiselect__tags-wrap img {
            height: 15px;
            border-radius: 50%;
        }

        .multiselect__tag-icon:after {
            color: white !important;
        }

        .multiselect__option--highlight {
            background: #00bf9c !important;
        }

        .multiselect__option--selected.multiselect__option--highlight {
            background: #00bf9c !important;
        }

        .multiselect__tags {
            border: 1px solid #b6bfc6 !important;
            border-radius: 0.125em !important;
            height: calc(1.875rem + 2px) !important;
        }

        .multiselect__tag {
            background: #788793 !important;
        }

        .multiselect__tag-icon:after {
            color: white !important;
        }
    </style>
@endsection

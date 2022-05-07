<?php
namespace Forums\Controllers;

use App\Controllers\Controller;
use Illuminate\Database\Capsule\Manager as DB;
use App\Models\User;
use Forums\Models\ForumBoardPermission;
use Forums\Models\ForumThread;
use Forums\Models\ForumPost;
use Forums\Models\ForumReaction;
use Forums\Models\ForumUser;
use App\Models\Notification;
use App\Models\Setting;
use League\HTMLToMarkdown\HtmlConverter;
use Forums\Models\ForumCategory;
use Forums\Models\ForumBoard;
use Forums\Models\ForumThreadsRead;
use App\Helpers;

class ForumsApiController extends Controller
{
    public function getIndex($request, $response) {
        $forumCategories = ForumCategory::get();
        foreach ($forumCategories as $fC) {
            $boards = $fC->boards()->with(['latest_thread','latest_thread.latest_post.user.group'])->get()->filter(function($board) {
                return !$board->permissions_extended[$this->auth->user()->gid_extended??0]['cannot_view'];
            });

            foreach($boards as $board) {
                $board->total_threads = $board->total_threads;
                $board->total_posts = $board->total_posts;

                if (isset($board->latest_thread)) {
                    $board->latest_thread->latest_post->user->steam = $board->latest_thread->latest_post->user->steam;
                }
            }

            $fC->boards = $boards;
        }

        $latestPosts = ForumPost::orderBy('timestamp', 'DESC')->with(['user.group','thread'])->take(5)->get()->filter(function($lP) {
            return !$lP->thread->board->permissions_extended[$this->auth->user()->gid_extended??0]['cannot_view'];
        });

        foreach ($latestPosts as $lP) {
          $lP->user->steam = $lP->user->steam;
        }

        return $response->withJSON([
          'forum_categories' => Helpers::whitelist_keys(json_decode(json_encode($forumCategories), true), [
              '*' => [
                  'cid',
                  'name',
                  'boards' => [
                      '*' => [
                          'bid',
                          'name',
                          'icon',
                          'total_threads',
                          'total_posts',
                          'latest_thread' => [
                              'topic',
                              'last_posted',
                              'last_post_steamid',
                              'latest_post' => [
                                  'pid',
                                  'timestamp',
                                  'user' => [
                                      'steamid',
                                      'steam' => [
                                          'personaname',
                                          'avatarfull'
                                      ],
                                      'group' => [
                                          'color'
                                      ]
                                  ]
                              ]
                          ]
                      ]
                  ]
              ]
          ]),
          'latest_posts' => Helpers::whitelist_keys(json_decode(json_encode($latestPosts), true), [
              '*' => [
                  'pid',
                  'tid',
                  'content',
                  'timestamp',
                  'last_edit',
                  'user' => [
                      'steamid',
                      'steam' => [
                          'personaname',
                          'avatarfull'
                      ],
                      'group' => [
                          'color'
                      ]
                  ],
                  'thread' => [
                      'topic'
                  ]
              ]
          ]),
          'forum_statistics' => [
              'total_posts' => ForumPost::count(),
              'total_threads' => ForumThread::count(),
              'total_users' => User::count()
          ]
        ]);
    }

    public function getSearch($request, $response, $args) {
        $params = $request->getParams();
        $perPage = 25;

        $gid = 0;
        if ($this->container->auth->check()) {
            $gid = $this->container->auth->user()->gid_extended;
        }

        $threads = ForumThread::disableCache()->whereHas('board', function ($query) use ($gid) {
            $query->whereHas('permissions_relation', function ($query) use ($gid) {
                $query->where('gid', $gid)->whereNull('cannot_view');
            })->orWhereDoesntHave('permissions_relation', function ($query) use ($gid) {
                $query->where('gid', $gid);
            });
        })->where(function ($query) use ($params) {
            $query->where('topic', 'like', "%{$params['keyword']}%")->orWhereHas('first_post', function ($query) use ($params) {
                $query->where('content', 'like', "%{$params['keyword']}%");
            })->orWhereHas('user', function ($query) use ($params) {
                $query->where('name', 'like', "%{$params['keyword']}%");
            })->orWhere('steamid', 'like', "%{$params['keyword']}%");
        });

        $threads = $threads->with(['user','user.group','latest_post','latest_post.user','latest_post.user.group','read_timestamp_relation'])->orderBy('pinned','DESC')->orderBy('last_posted','DESC')->paginate($perPage, ['*'], 'page', $params['page'] ?? 1);
        foreach ($threads as $thread) {
            $thread->postcount = $thread->posts()->count();
            $thread->user->steam = $thread->user->steam;
            if ($thread->latest_post) {
                $thread->latest_post->user->steam = $thread->latest_post->user->steam;
            }
            $thread->read_timestamp = $thread->read_timestamp;
        }

        return $response->withJSON([
          'threads' => Helpers::whitelist_keys(json_decode(json_encode($threads), true), [
            'current_page',
            'last_page',
              'data' => [
                  '*' => [
                      'tid',
                      'bid',
                      'steamid',
                      'topic',
                      'timestamp',
                      'last_posted',
                      'locked',
                      'pinned',
                      'postcount',
                      'read_timestamp',
                      'user' => [
                          'steamid',
                          'steam' => [
                              'personaname',
                              'avatarfull'
                          ],
                          'group' => [
                              'color'
                          ]
                      ],
                      'latest_post' => [
                          'timestamp',
                          'user' => [
                              'steamid',
                              'steam' => [
                                  'personaname',
                                  'avatarfull'
                              ],
                              'group' => [
                                  'color'
                              ]
                          ]
                      ]
                  ]
              ]
          ])
        ]);
    }

    public function getBoard($request, $response, $args) {
      $perPage = 25;
      $board = ForumBoard::find($args['bid']);

      if ($board == null) {
          return $response->withStatus(404);
      }

      $threads = ForumThread::where('bid', $args['bid'])->with(['user','user.group','latest_post','latest_post.user','latest_post.user.group','read_timestamp_relation'])->orderBy('pinned','DESC')->orderBy('last_posted','DESC')->paginate($perPage, ['*'], 'page', $args['page'] ?? 1);
      foreach ($threads as $thread) {
          $thread->postcount = $thread->posts()->count();
          $thread->user->steam = $thread->user->steam;
          if ($thread->latest_post) {
              $thread->latest_post->user->steam = $thread->latest_post->user->steam;
          }
          $thread->read_timestamp = $thread->read_timestamp;
      }

      $totalPages = ceil($threads->count()/$perPage) ?? 1;

      return $response->withJSON([
        'board' => $board,
        'threads' => Helpers::whitelist_keys(json_decode(json_encode($threads), true), [
          'current_page',
          'last_page',
            'data' => [
                '*' => [
                    'tid',
                    'bid',
                    'steamid',
                    'topic',
                    'timestamp',
                    'last_posted',
                    'locked',
                    'pinned',
                    'postcount',
                    'read_timestamp',
                    'user' => [
                        'steamid',
                        'steam' => [
                            'personaname',
                            'avatarfull'
                        ],
                        'group' => [
                            'color'
                        ]
                    ],
                    'latest_post' => [
                        'timestamp',
                        'user' => [
                            'steamid',
                            'steam' => [
                                'personaname',
                                'avatarfull'
                            ],
                            'group' => [
                                'color'
                            ]
                        ]
                    ]
                ]
            ]
        ])
      ]);
    }

    public function getThread($request, $response, $args) {
        $perPage = 15;
        $thread = ForumThread::find($args['tid']);

        if ($thread == null) {
            return $response->withStatus(404);
        }

        $posts = $thread->posts()->with(['reactions','quotedPost','quotedPost.user','quotedPost.user.group','user','user.group'])->paginate($perPage, ['*'], 'page', $args['page'] ?? 1)->keyBy('pid');
        foreach($posts as $post) {
            $post->setRelation('reactions', $post->reactions->groupBy('rname'));
            $post->user->steam = $post->user->steam;
            if ($post->quotedPost) {
                $post->quotedPost->user->steam = $post->quotedPost->user->steam;
            }
            $post->user->post_count = $post->user->posts()->count();
        }

        $totalPages = ceil($thread->posts()->count()/$perPage) ?? 1;

        if ($this->auth->check()) {
            ForumThreadsRead::updateOrCreate(['steamid'=>$this->auth->user()->steamid,'tid'=>$args['tid']],['timestamp'=>DB::raw('NOW()')])->flushCache();
        }

        return $response->withJSON([
          'board' => ForumBoard::find($thread->bid),
          'thread' => $thread,
          'posts' => Helpers::whitelist_keys(json_decode(json_encode($posts), true), [
              '*' => [
                  'pid',
                  'reply_to_pid',
                  'content',
                  'timestamp',
                  'last_edit',
                  'reactions',
                  'quoted_post' => [
                      'pid',
                      'content',
                      'user' => [
                          'steamid',
                          'steam' => [
                              'personaname',
                              'avatarfull'
                          ],
                          'group' => [
                              'color',
                          ]
                      ]
                  ],
                  'user' => [
                      'steamid',
                      'gid',
                      'steam' => [
                          'personaname',
                          'avatarfull'
                      ],
                      'status',
                      'post_count',
                      'group' => [
                          'name',
                          'color',
                          'icon'
                      ]
                  ]
              ]
          ]),
          'reactions_enabled' => Setting::find('reactions_enabled')->value??false,
          'pagination' => [
              'current' => $args['page'] ?? 1,
              'total' => $totalPages
          ]
        ]);
    }

    public function getPost($request, $response, $args) {
        $perPage = 15;
        $post = ForumPost::find($args['pid']);
        if ($post == null) {
            return $response->withStatus(404);
        }
        $thread = $post->thread;

        $postIndex = 1;
        foreach ($thread->posts as $post) {
            if ($args['pid'] == $post->pid) {
                $page = ceil($postIndex/$perPage);
                break;
            }
            $postIndex++;
        }

        return $response->withJSON([
          'tid' => $thread->tid,
          'page' => $page
        ]);
    }

    static function contentToMarkdown($content) {
        $converter = new HtmlConverter([
            'strip_tags' => true,
            'hard_break' => true,
            'header_style' => 'atx'
        ]);
        $mdContent = $converter->convert($content);
        return str_replace('<pre class="ql-syntax" spellcheck="false">', '', $mdContent); // workaround for https://github.com/thephpleague/html-to-markdown/issues/130
    }

    public function create($request, $response, $args) {
        $params = $request->getParams();
        $user = $this->auth->user();

        $gid = $user->gid_extended;
        $perms = ForumBoardPermission::where('bid',$params['bid'])->where('gid',$gid)->first();
        if ($perms['cannot_post_thread'] === 1 || !isset($perms) && $gid === -1) {
            return $response->withStatus(403);
        }

        $thread = ForumThread::create([
          'bid' => $params['bid'],
          'steamid' => $_SESSION['steamid'],
          'topic' => $params['topic']
        ]);

        ForumPost::create([
          'tid' => $thread->tid,
          'steamid' => $_SESSION['steamid'],
          'content' => self::contentToMarkdown($params['content'])
        ]);
        $thread->flushCache();
        return $response->withJSON(['status'=>'success','tid'=>$thread->tid]);
    }

    public function reply($request, $response, $args) {
        $params = $request->getParams();
        $user = $this->auth->user();
        $thread = ForumThread::find($params['tid']);
        $bid = $thread->bid;

        $gid = $user->gid_extended;
        $perms = ForumBoardPermission::where('bid',$bid)->where('gid',$gid)->first();
        if ($perms['cannot_post_reply'] === 1 || !isset($perms) && $gid === -1) {
            return $response->withStatus(403);
        }

        $post = ForumPost::create([
          'tid' => $params['tid'],
          'steamid' => $_SESSION['steamid'],
          'content' => self::contentToMarkdown($params['content']),
          'reply_to_pid' => is_numeric($params['reply_to_pid'])? $params['reply_to_pid']: null
        ]);
        $post->flushCache();

        $thread->update(['last_posted'=>DB::raw('NOW()')]);

        $newPage = ceil(ForumThread::find($post->tid)->posts->count()/15) ?? 1;

        $alreadyNotified = array();

        $dom = new \DomDocument();
        libxml_use_internal_errors(true);
        $dom->loadHTML($params['content']);
        libxml_clear_errors();
        $nodes = $dom->getElementsByTagName('span');
        foreach ($nodes as $node) {
            $class = $node->getAttribute('class');
            if ($class === 'mention') {
                $steamid = $node->getAttribute('data-id');
                if (!in_array($steamid,$alreadyNotified)) {
                    array_push($alreadyNotified,$steamid);
                    $notifciation = User::find($steamid)->notifications()->create(['type'=>'forums_mention', 'json'=>['ref_steamid'=>$user->steamid,'tid'=>$params['tid'],'pid'=>$post->pid,'page'=>$newPage]])->flushCache();
                }
            }
        }

        if ($post->quotedPost) {
            $quotedUser = $post->quotedPost->user;
            if (!in_array($quotedUser->steamid,$alreadyNotified)) {
                $quotedUser->notifications()->create(['type'=>'forums_quote', 'json'=>['ref_steamid'=>$user->steamid,'tid'=>$params['tid'],'pid'=>$post->pid,'page'=>$newPage]])->flushCache();
            }
        }

        return $response->withJSON(['status'=>'success','pid'=>$post->pid,'page'=>$newPage]);
    }

    public function edit($request, $response, $args) {
        $params = $request->getParams();
        $post = ForumPost::find($params['pid']);

        if ($this->auth->user()->steamid !== $post->steamid && !$this->auth->canModerateForums()) {
            return $response->withStatus(403);
        }

        if ($post->steamid == $this->auth->user()->steamid) { // TODO: allow moderators to edit posts from other users (and log edits)
            $post->update(['content'=>self::contentToMarkdown($params['content'])]);
            return $response->withJSON(['status'=>'success','pid'=>$post->pid]);
        } else {
            return $response->withStatus(403);
        }
    }

    public function delete($request, $response, $args) {
        $params = $request->getParams();
        $post = ForumPost::find($params['pid']);

        if ($this->auth->user()->steamid !== $post->steamid && !$this->auth->canModerateForums()) {
            return $response->withStatus(403);
        }

        $thread = ForumThread::find($params['tid']);

        if ($post->pid === $thread->first_post->pid) {
            $thread->delete();
        } else {
            $post->delete();
        }

        return $response->withJSON(['status'=>'success']);
    }

    public function mentionAutoComplete($request, $response, $args) {
        $params = $request->getParams();
        $likeStr = '%'.str_replace('@','',$params['matchStr']).'%';
        $users = ForumUser::where('name', 'like', $likeStr)->take(10)->get();
        foreach ($users as $user) {
            $user->steam = $user->steam;
        }
        return $response->withJSON(['status'=>'success','results'=>$users]);
    }

    public function react($request, $response, $args) {
        $params = $request->getParams();
        $user = $this->auth->user();

        $post = ForumPost::find($params['pid']);

        $tid = $post->tid;
        $bid = ForumThread::find($tid)->bid;

        $gid = $this->auth->user()->gid_extended;
        $perms = ForumBoardPermission::where('bid',$bid)->where('gid',$gid)->first();
        if ($perms['cannot_react'] === 1 || !isset($perms) && $gid === -1) {
            return $response->withStatus(403);
        }

        $settings = Setting::where('category','forums')->get()->keyBy('setting')->toArray();
        if ($settings['reactions_enabled']['value'] && $post->steamid != $user->steamid) {
            $reaction = ForumReaction::where(['steamid'=>$user->steamid])->where(['rname'=>$params['rname']])->where(['pid'=>$params['pid']])->first();
            if ($reaction == null) {
                $count = ForumReaction::where(['steamid'=>$user->steamid])->where(['pid'=>$params['pid']])->count();
                if ($count < $settings['max_reactions_per_user_per_post']['value']) {
                    $reaction = ForumReaction::create(['steamid'=>$user->steamid,'rname'=>$params['rname'],'pid'=>$params['pid']]);
                    $reaction->flushCache();
                } else {
                    return $response->withJSON(['status'=>'max_reactions_reached']);
                }
            } else {
                $reaction->delete();
            }
        }
        return $response->withJSON(['status'=>'success','reactions'=>ForumReaction::where(['pid'=>$params['pid']])->get()->keyBy('rname')??null]);
    }

    public function toggleThreadState($request, $response, $args) {
        $params = $request->getParams();
        $newVal = null; if ($params['val'] == 1) { $newVal = 1; }
        ForumThread::where('tid',$params['tid'])->update([$params['state']=>$newVal]);
        return $response->withJSON(['status'=>'success']);
    }

    public function moveThread($request, $response, $args) {
        $params = $request->getParams();
        ForumThread::where('tid',$params['tid'])->update(['bid'=>$params['bid']]);
        return $response->withJSON(['status'=>'success']);
    }
}

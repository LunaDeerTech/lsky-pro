@section('title', '图片管理')

@push('styles')
    <link rel="stylesheet" href="{{ asset('css/justified-gallery/justifiedGallery.min.css') }}">
    <link rel="stylesheet" href="{{ asset('css/viewer-js/viewer.min.css') }}">
    <link rel="stylesheet" href="{{ asset('css/context-js/context-js.css') }}">
@endpush

<x-app-layout>
    <div class="p-2">
        <form class="w-full flex items-center justify-center py-3 md:py-5 lg:py-7" action="{{ route('admin.images') }}" method="get">
            <div class="w-full md:w-[70%] lg:w-[60%] flex flex-col">
                <input class="px-4 py-2 text-md rounded-md bg-white" name="keywords" placeholder="输入关键字回车搜索..." value="{{ request('keywords') }}" />
                <div class="w-full flex justify-end">
                    <a href="javascript:void(0)" id="grammar" class="inline-block mt-2 text-xs text-gray-600">高级搜索语法</a>
                </div>
            </div>
        </form>

        <!-- 批量操作控制栏 -->
        <div id="batch-control" class="hidden mb-3 p-3 bg-blue-50 rounded-lg border border-blue-200">
            <div class="flex items-center justify-between">
                <div class="flex items-center space-x-4">
                    <span class="text-sm font-medium text-blue-800">已选择 <span id="selected-count">0</span> 张图片</span>
                    <button type="button" id="select-all" class="text-xs text-blue-600 hover:text-blue-800 font-medium">全选</button>
                    <button type="button" id="select-none" class="text-xs text-blue-600 hover:text-blue-800">取消全选</button>
                    <button type="button" id="select-reverse" class="text-xs text-blue-600 hover:text-blue-800">反选</button>
                </div>
                <div class="flex items-center space-x-2">
                    <button type="button" id="batch-delete" class="px-3 py-1 bg-red-500 text-white text-sm rounded hover:bg-red-600 transition-colors flex items-center space-x-1">
                        <span>批量删除</span>
                    </button>
                    <button type="button" id="batch-cancel" class="px-3 py-1 bg-gray-300 text-gray-700 text-sm rounded hover:bg-gray-400 transition-colors">取消批量</button>
                </div>
            </div>
            <div class="mt-2 text-xs text-gray-600">
                提示：点击图片可切换选中，Ctrl+点击可多选，Delete键删除，Esc退出批量模式
            </div>
        </div>

        <!-- 批量操作工具栏 -->
        <div class="relative flex justify-between items-center px-2 py-2 mb-4 z-[3] bg-white border border-gray-200 rounded">
            <div class="space-x-2 flex justify-between items-center">
                <span id="selection-info" class="text-sm text-gray-600">选择图片进行批量操作</span>
                <div class="flex-row hidden lg:flex">
                    <a data-operate="permission" class="hidden text-sm py-2 px-3 hover:bg-gray-100 rounded text-gray-800" href="javascript:void(0)">设置权限</a>
                    <a data-operate="unhealthy" class="hidden text-sm py-2 px-3 hover:bg-gray-100 rounded text-gray-800" href="javascript:void(0)">标记违规</a>
                    <a data-operate="healthy" class="hidden text-sm py-2 px-3 hover:bg-gray-100 rounded text-gray-800" href="javascript:void(0)">取消违规</a>
                    <a data-operate="delete" class="hidden text-sm py-2 px-3 hover:bg-gray-100 rounded text-red-600" href="javascript:void(0)">批量删除</a>
                </div>
                <div class="block lg:hidden">
                    <x-dropdown direction="right">
                        <x-slot name="trigger">
                            <a class="text-sm py-2 px-3 hover:bg-gray-100 rounded text-gray-800" href="javascript:void(0)"><i class="fas fa-ellipsis-h text-blue-500"></i></a>
                        </x-slot>
                        <x-slot name="content">
                            <x-dropdown-link data-operate="permission" class="hidden" href="javascript:void(0)" @click="open = false">设置权限</x-dropdown-link>
                            <x-dropdown-link data-operate="unhealthy" class="hidden" href="javascript:void(0)" @click="open = false">标记违规</x-dropdown-link>
                            <x-dropdown-link data-operate="healthy" class="hidden" href="javascript:void(0)" @click="open = false">取消违规</x-dropdown-link>
                            <x-dropdown-link data-operate="delete" class="hidden" href="javascript:void(0)" @click="open = false">批量删除</x-dropdown-link>
                        </x-slot>
                    </x-dropdown>
                </div>
            </div>
            <div class="flex space-x-2 items-center">
                <a id="select-all" class="text-sm py-1 px-2 bg-blue-500 text-white rounded hover:bg-blue-600" href="javascript:void(0)">全选</a>
                <a id="clear-selection" class="text-sm py-1 px-2 bg-gray-500 text-white rounded hover:bg-gray-600" href="javascript:void(0)">清空</a>
            </div>
        </div>

        @if($images->isNotEmpty())
            <div class="grid grid-cols-2 md:grid-cols-2 lg:grid-cols-4 xl:grid-cols-6 2xl:grid-cols-8 gap-2" id="image-grid">
            <div id="images-container" class="grid grid-cols-2 md:grid-cols-2 lg:grid-cols-4 xl:grid-cols-6 2xl:grid-cols-8 gap-2 dragselect">
                @foreach($images as $image)
                <div data-json="{{ htmlspecialchars($image->toJson(), ENT_QUOTES, 'UTF-8') }}" data-id="{{ $image->id }}" class="item relative flex flex-col items-center justify-center overflow-hidden rounded-md cursor-pointer group select-none">
                    <!-- 批量选择复选框（左上角） -->
                    <div class="batch-checkbox absolute top-1 left-1 z-[2] hidden group-hover:block">
                        <input type="checkbox" class="image-checkbox w-4 h-4 cursor-pointer" data-id="{{ $image->id }}" />
                    </div>
                    <!-- 始终显示的复选框（当批量模式激活时，左上角） -->
                    <div class="batch-checkbox-static absolute top-1 left-1 z-[2] hidden">
                        <input type="checkbox" class="image-checkbox w-4 h-4 cursor-pointer" data-id="{{ $image->id }}" />
                    </div>
                    <!-- 状态标签和删除按钮区域 -->
                    <div class="absolute top-1 right-1 z-[1] flex flex-col items-end space-y-1">
                        <!-- 状态标签 -->
                        <div class="flex space-x-1">
                            @if($image->is_unhealthy)
                                <span class="bg-red-500 text-white rounded-md text-sm px-1 py-0">违规</span>
                            @endif
                            @if($image->extension === 'gif')
                                <span class="bg-white rounded-md text-sm px-1 py-0">Gif</span>
                            @endif
                        </div>
                        <!-- 删除按钮（仅在悬停时显示） -->
                        <div class="hidden group-hover:block">
                            <i data-id="{{ $image->id }}" class="delete fas fa-trash text-red-500 w-4 h-4"></i>
                        </div>
                    </div>

                    <div class="w-full h-36 relative">
                        <img class="w-full h-full object-cover" src="{{ $image->thumb_url }}" alt="{{ $image->name }}" onerror="this.style.display='none'; this.parentElement.innerHTML='<div class=\'w-full h-full flex items-center justify-center bg-gray-100 text-gray-400\'>图片加载失败</div>'">
                <div data-json='{{ $image->toJson() }}' class="item relative flex flex-col items-center justify-center overflow-hidden rounded-md cursor-pointer group dragselect-item" data-id="{{ $image->id }}">
                    <div class="image-selector absolute z-[2] top-1 left-1 overflow-hidden cursor-pointer hidden group-hover:block">
                        <div class="p-1 text-xl">
                            <i class="fas fa-check-circle block rounded-full bg-white text-white border border-gray-500"></i>
                        </div>
                    </div>
                    <div class="flex absolute top-1 left-1 z-[1] space-x-1">
                        @if($image->is_unhealthy)
                            <span class="bg-red-500 text-white rounded-md text-sm px-1 py-0">违规</span>
                        @endif
                        @if($image->extension === 'gif')
                            <span class="bg-white rounded-md text-sm px-1 py-0">Gif</span>
                        @endif
                    </div>
                    <img class="w-full h-36 object-cover transition-all group-hover:brightness-50" src="{{ $image->thumb_url }}">

                    <div class="absolute top-1 right-1 space-x-1 hidden group-hover:flex">
                        <i data-id="{{ $image->id }}" class="delete fas fa-trash text-red-500 w-4 h-4 bg-white rounded p-1"></i>
                    </div>

                    <div class="p-2 bg-white w-full flex items-center">
                        @if($image->user)
                            <div class="item-user flex items-center">
                                <img src="{{ $image->user->avatar }}" class="w-6 h-6 rounded-full">
                                <span class="ml-2 truncate group-hover:text-blue-500">{{ $image->user->name }}</span>
                            </div>
                        @else
                            <div class="w-6 h-6 rounded-full overflow-hidden">
                                <x-default-avatar/>
                            </div>
                            <span class="ml-2">游客</span>
                        @endif
                    </div>
                </div>
                @endforeach
            </div>
            <div class="mt-2">
                {{ $images->links() }}
            </div>
        @else
            <x-no-data message="这里还是空的～" />
        @endif
    </div>

    <x-modal id="content-modal">
        <div id="modal-content"></div>
    </x-modal>

    <script type="text/html" id="image-tpl">
        <div class="w-full mt-4">
            <div class="w-full mb-4 rounded-sm overflow-hidden flex items-center justify-center">
                <a class="w-full" href="__url__" target="_blank">
                    <img src="__url__" alt="__name__" class="w-full object-center object-cover">
                </a>
            </div>
            <div class="relative rounded-md bg-white mb-8 overflow-hidden">
                <dl>
                    <div class="bg-gray-50 px-2 py-3 sm:grid sm:grid-cols-3 sm:gap-4 sm:px-6">
                        <dt class="text-sm font-medium text-gray-500">上传用户</dt>
                        <dd class="mt-1 text-sm text-gray-900 sm:mt-0 sm:col-span-2 truncate">__user_name__</dd>
                    </div>
                </dl>
                <dl>
                    <div class="bg-white px-2 py-3 sm:grid sm:grid-cols-3 sm:gap-4 sm:px-6">
                        <dt class="text-sm font-medium text-gray-500">相册</dt>
                        <dd class="mt-1 text-sm text-gray-900 sm:mt-0 sm:col-span-2 truncate">__album_name__</dd>
                    </div>
                </dl>
                <dl>
                    <div class="bg-gray-50 px-2 py-3 sm:grid sm:grid-cols-3 sm:gap-4 sm:px-6">
                        <dt class="text-sm font-medium text-gray-500">角色组</dt>
                        <dd class="mt-1 text-sm text-gray-900 sm:mt-0 sm:col-span-2 truncate">__group_name__</dd>
                    </div>
                </dl>
                <dl>
                    <div class="bg-white px-2 py-3 sm:grid sm:grid-cols-3 sm:gap-4 sm:px-6">
                        <dt class="text-sm font-medium text-gray-500">储存策略</dt>
                        <dd class="mt-1 text-sm text-gray-900 sm:mt-0 sm:col-span-2 truncate">__strategy_name__</dd>
                    </div>
                </dl>
                <dl>
                    <div class="bg-gray-50 px-2 py-3 sm:grid sm:grid-cols-3 sm:gap-4 sm:px-6">
                        <dt class="text-sm font-medium text-gray-500">图片名称</dt>
                        <dd class="mt-1 text-sm text-gray-900 sm:mt-0 sm:col-span-2 truncate">__name__</dd>
                    </div>
                </dl>
                <dl>
                    <div class="bg-white px-2 py-3 sm:grid sm:grid-cols-3 sm:gap-4 sm:px-6">
                        <dt class="text-sm font-medium text-gray-500">原始名称</dt>
                        <dd class="mt-1 text-sm text-gray-900 sm:mt-0 sm:col-span-2 truncate">__origin_name__</dd>
                    </div>
                </dl>
                <dl>
                    <div class="bg-gray-50 px-2 py-3 sm:grid sm:grid-cols-3 sm:gap-4 sm:px-6">
                        <dt class="text-sm font-medium text-gray-500">物理路径</dt>
                        <dd class="mt-1 text-sm text-gray-900 sm:mt-0 sm:col-span-2 truncate">__pathname__</dd>
                    </div>
                </dl>
                <dl>
                    <div class="bg-white px-2 py-3 sm:grid sm:grid-cols-3 sm:gap-4 sm:px-6">
                        <dt class="text-sm font-medium text-gray-500">图片大小</dt>
                        <dd class="mt-1 text-sm text-gray-900 sm:mt-0 sm:col-span-2 truncate">__size__</dd>
                    </div>
                </dl>
                <dl>
                    <div class="bg-gray-50 px-2 py-3 sm:grid sm:grid-cols-3 sm:gap-4 sm:px-6">
                        <dt class="text-sm font-medium text-gray-500">图片类型</dt>
                        <dd class="mt-1 text-sm text-gray-900 sm:mt-0 sm:col-span-2 truncate">__mimetype__</dd>
                    </div>
                </dl>
                <dl>
                    <div class="bg-white px-2 py-3 sm:grid sm:grid-cols-3 sm:gap-4 sm:px-6">
                        <dt class="text-sm font-medium text-gray-500">MD5</dt>
                        <dd class="mt-1 text-sm text-gray-900 sm:mt-0 sm:col-span-2 truncate">__md5__</dd>
                    </div>
                </dl>
                <dl>
                    <div class="bg-gray-50 px-2 py-3 sm:grid sm:grid-cols-3 sm:gap-4 sm:px-6">
                        <dt class="text-sm font-medium text-gray-500">SHA1</dt>
                        <dd class="mt-1 text-sm text-gray-900 sm:mt-0 sm:col-span-2 truncate">__sha1__</dd>
                    </div>
                </dl>
                <dl>
                    <div class="bg-white px-2 py-3 sm:grid sm:grid-cols-3 sm:gap-4 sm:px-6">
                        <dt class="text-sm font-medium text-gray-500">尺寸</dt>
                        <dd class="mt-1 text-sm text-gray-900 sm:mt-0 sm:col-span-2 truncate">__width__*__height__</dd>
                    </div>
                </dl>
                <dl>
                    <div class="bg-gray-50 px-2 py-3 sm:grid sm:grid-cols-3 sm:gap-4 sm:px-6">
                        <dt class="text-sm font-medium text-gray-500">权限</dt>
                        <dd class="mt-1 text-sm text-gray-900 sm:mt-0 sm:col-span-2 truncate">__permission__</dd>
                    </div>
                </dl>
                <dl>
                    <div class="bg-white px-2 py-3 sm:grid sm:grid-cols-3 sm:gap-4 sm:px-6">
                        <dt class="text-sm font-medium text-gray-500">不健康的</dt>
                        <dd class="mt-1 text-sm text-gray-900 sm:mt-0 sm:col-span-2 truncate">__is_unhealthy__</dd>
                    </div>
                </dl>
                <dl>
                    <div class="bg-gray-50 px-2 py-3 sm:grid sm:grid-cols-3 sm:gap-4 sm:px-6">
                        <dt class="text-sm font-medium text-gray-500">上传 IP</dt>
                        <dd class="mt-1 text-sm text-gray-900 sm:mt-0 sm:col-span-2 truncate">__uploaded_ip__</dd>
                    </div>
                </dl>
                <dl>
                    <div class="bg-white px-2 py-3 sm:grid sm:grid-cols-3 sm:gap-4 sm:px-6">
                        <dt class="text-sm font-medium text-gray-500">上传时间</dt>
                        <dd class="mt-1 text-sm text-gray-900 sm:mt-0 sm:col-span-2 truncate">__created_at__</dd>
                    </div>
                </dl>
            </div>

            <a href="javascript:void(0)" data-id="__id__" class="delete inline-flex justify-center py-1 px-2 border border-transparent shadow-sm text-sm font-medium rounded-md text-white focus:outline-none focus:ring-2 focus:ring-offset-2 focus:ring-indigo-500 float-right bg-red-500">
                删除
            </a>
        </div>
    </script>

    <script type="text/html" id="user-tpl">
        <div class="flex w-full items-center justify-center py-4">
            <img class="rounded-full h-24 w-24" src="__avatar__">
        </div>
        <div class="relative rounded-md bg-white mb-8 overflow-hidden">
            <dl>
                <div class="bg-white px-2 py-3 sm:grid sm:grid-cols-3 sm:gap-4 sm:px-6">
                    <dt class="text-sm font-medium text-gray-500">用户名</dt>
                    <dd class="mt-1 text-sm text-gray-900 sm:mt-0 sm:col-span-2 truncate">__name__</dd>
                </div>
            </dl>
            <dl>
                <div class="bg-gray-50 px-2 py-3 sm:grid sm:grid-cols-3 sm:gap-4 sm:px-6">
                    <dt class="text-sm font-medium text-gray-500">邮箱</dt>
                    <dd class="mt-1 text-sm text-gray-900 sm:mt-0 sm:col-span-2 truncate">__email__</dd>
                </div>
            </dl>
            <dl>
                <div class="bg-white px-2 py-3 sm:grid sm:grid-cols-3 sm:gap-4 sm:px-6">
                    <dt class="text-sm font-medium text-gray-500">总容量</dt>
                    <dd class="mt-1 text-sm text-gray-900 sm:mt-0 sm:col-span-2 truncate">__capacity__</dd>
                </div>
            </dl>
            <dl>
                <div class="bg-gray-50 px-2 py-3 sm:grid sm:grid-cols-3 sm:gap-4 sm:px-6">
                    <dt class="text-sm font-medium text-gray-500">已用容量</dt>
                    <dd class="mt-1 text-sm text-gray-900 sm:mt-0 sm:col-span-2 truncate">__used_capacity__</dd>
                </div>
            </dl>
            <dl>
                <div class="bg-white px-2 py-3 sm:grid sm:grid-cols-3 sm:gap-4 sm:px-6">
                    <dt class="text-sm font-medium text-gray-500">图片数量</dt>
                    <dd class="mt-1 text-sm text-gray-900 sm:mt-0 sm:col-span-2 truncate">__image_num__</dd>
                </div>
            </dl>
            <dl>
                <div class="bg-gray-50 px-2 py-3 sm:grid sm:grid-cols-3 sm:gap-4 sm:px-6">
                    <dt class="text-sm font-medium text-gray-500">相册数量</dt>
                    <dd class="mt-1 text-sm text-gray-900 sm:mt-0 sm:col-span-2 truncate">__album_num__</dd>
                </div>
            </dl>
            <dl>
                <div class="bg-white px-2 py-3 sm:grid sm:grid-cols-3 sm:gap-4 sm:px-6">
                    <dt class="text-sm font-medium text-gray-500">注册 IP</dt>
                    <dd class="mt-1 text-sm text-gray-900 sm:mt-0 sm:col-span-2 truncate">__registered_ip__</dd>
                </div>
            </dl>
            <dl>
                <div class="bg-gray-50 px-2 py-3 sm:grid sm:grid-cols-3 sm:gap-4 sm:px-6">
                    <dt class="text-sm font-medium text-gray-500">邮箱验证时间</dt>
                    <dd class="mt-1 text-sm text-gray-900 sm:mt-0 sm:col-span-2 truncate">__email_verified_at__</dd>
                </div>
            </dl>
            <dl>
                <div class="bg-white px-2 py-3 sm:grid sm:grid-cols-3 sm:gap-4 sm:px-6">
                    <dt class="text-sm font-medium text-gray-500">注册时间</dt>
                    <dd class="mt-1 text-sm text-gray-900 sm:mt-0 sm:col-span-2 truncate">__created_at__</dd>
                </div>
            </dl>
        </div>
    </script>

    <script type="text/html" id="search-grammar-tpl">
        <p class="text-gray-600">默认输入关键字搜索会根据图片的别名、原始名称进行匹配，你也可以使用下面的搜索语法进行高级搜索，并可以以任意组合使用这些搜索限定符来缩小结果范围。例如查找用户名为张三，邮箱为 a@qq.com 且图片拓展名为 jpg 的所有图片：</p>
        <p class="text-gray-600 mb-2"><b>name:张三 email:a@qq.com extension:jpg</b></p>
        <table class="min-w-full divide-y divide-gray-200">
            <thead class="bg-gray-50">
            <tr>
                <th scope="col" class="px-3 py-3 text-left text-xs font-medium text-gray-500 uppercase tracking-wider whitespace-nowrap">
                    限定符
                </th>
                <th scope="col" class="px-3 py-3 text-left text-xs font-medium text-gray-500 uppercase tracking-wider whitespace-nowrap">
                    示例
                </th>
            </tr>
            </thead>
            <tbody class="bg-white divide-y divide-gray-200">
            <tr>
                <td class="px-3 py-2 text-gray-600 text-sm">name:<span class="italic">USERNAME</span></td>
                <td class="px-3 py-2 text-sm">匹配用户名为 USERNAME 的图片</td>
            </tr>
            <tr>
                <td class="px-3 py-2 text-gray-600 text-sm">album:<span class="italic">ALBUM_NAME</span></td>
                <td class="px-3 py-2 text-sm">匹配所在相册名称为 ALBUM_NAME 的图片</td>
            </tr>
            <tr>
                <td class="px-3 py-2 text-gray-600 text-sm">group:<span class="italic">GROUP_NAME</span></td>
                <td class="px-3 py-2 text-sm">匹配图片所属组名称为 GROUP_NAME 的图片</td>
            </tr>
            <tr>
                <td class="px-3 py-2 text-gray-600 text-sm">strategy:<span class="italic">STRATEGY_NAME</span></td>
                <td class="px-3 py-2 text-sm">匹配图片所属策略名称为 STRATEGY_NAME 的图片</td>
            </tr>
            <tr>
                <td class="px-3 py-2 text-gray-600 text-sm">email:<span class="italic">EMAIL</span></td>
                <td class="px-3 py-2 text-sm">匹配用户邮箱为 EMAIL 的图片</td>
            </tr>
            <tr>
                <td class="px-3 py-2 text-gray-600 text-sm">extension:<span class="italic">EXTENSION</span></td>
                <td class="px-3 py-2 text-sm">匹配图片拓展名为 EXTENSION 的图片</td>
            </tr>
            <tr>
                <td class="px-3 py-2 text-gray-600 text-sm">md5:<span class="italic">FILE_MD5</span></td>
                <td class="px-3 py-2 text-sm">匹配图片文件 md5 值名为 FILE_MD5 的图片</td>
            </tr>
            <tr>
                <td class="px-3 py-2 text-gray-600 text-sm">sha1:<span class="italic">FILE_SHA1</span></td>
                <td class="px-3 py-2 text-sm">匹配图片文件 sha1 值名为 FILE_SHA1 的图片</td>
            </tr>
            <tr>
                <td class="px-3 py-2 text-gray-600 text-sm">ip:<span class="italic">UPLOAD_IP</span></td>
                <td class="px-3 py-2 text-sm">匹配上传 IP 为 UPLOAD_IP 的图片</td>
            </tr>
            <tr>
                <td class="px-3 py-2 text-gray-600 text-sm">is:public</td>
                <td class="px-3 py-2 text-sm">匹配公开的图片</td>
            </tr>
            <tr>
                <td class="px-3 py-2 text-gray-600 text-sm">is:private</td>
                <td class="px-3 py-2 text-sm">匹配私有的图片</td>
            </tr>
            <tr>
                <td class="px-3 py-2 text-gray-600 text-sm">is:unhealthy</td>
                <td class="px-3 py-2 text-sm">匹配不健康的图片</td>
            </tr>
            <tr>
                <td class="px-3 py-2 text-gray-600 text-sm">is:guest</td>
                <td class="px-3 py-2 text-sm">匹配游客上传的图片</td>
            </tr>
            <tr>
                <td class="px-3 py-2 text-gray-600 text-sm">is:adminer</td>
                <td class="px-3 py-2 text-sm">匹配管理员上传的图片</td>
            </tr>
            <tr>
                <td class="px-3 py-2 text-gray-600 text-sm">order:earliest</td>
                <td class="px-3 py-2 text-sm">按最早上传的进行排序</td>
            </tr>
            <tr>
                <td class="px-3 py-2 text-gray-600 text-sm">order:utmost</td>
                <td class="px-3 py-2 text-sm">按图片大小，从大到小进行排序</td>
            </tr>
            <tr>
                <td class="px-3 py-2 text-gray-600 text-sm">order:least</td>
                <td class="px-3 py-2 text-sm">按图片大小，从小到大进行排序</td>
            </tr>
            </tbody>
        </table>
    </script>

@push('scripts')
        <script src="{{ asset('js/dragselect/ds.min.js') }}"></script>
        <script src="{{ asset('js/context-js/context-js.js') }}"></script>
        <script>
            let modal = Alpine.store('modal');

            // 拖拽选择相关变量
            const IMAGES_CONTAINER = '#images-container';
            const IMAGES_ITEM = '.dragselect-item';
            let ds = null;

            // 初始化拖拽选择
            function initDragSelect() {
                if ($(IMAGES_CONTAINER).length && $(IMAGES_ITEM).length) {
                    ds = new DragSelect({
                        area: $(IMAGES_CONTAINER).get(0),
                        keyboardDrag: false,
                    });

                    ds.subscribe('elementselect', _ => updateSelection());
                    ds.subscribe('elementunselect', _ => updateSelection());
                    ds.subscribe('predragstart', ({ event }) => {
                        if (!$(event.target).hasClass('dragselect') && !$(event.target).closest('.dragselect-item').length) {
                            ds.break();
                        }
                    });

                    // 点击选择器图标进行单选
                    $(document).off('click', '.image-selector').on('click', '.image-selector', function(e) {
                        e.stopPropagation();
                        ds.toggleSelection($(this).closest('.dragselect-item'));
                        updateSelection();
                    });
                }
            }

            // 更新选择状态显示
            function updateSelection() {
                if (!ds) return;
                
                let selected = ds.getSelection();
                let count = selected.length;
                
                if (count > 0) {
                    $('#selection-info').text(`已选择 ${count} 张图片`);
                    // 显示对应的操作按钮
                    $('[data-operate]').hide();
                    if (count === 1) {
                        $('[data-operate="permission"], [data-operate="unhealthy"], [data-operate="healthy"], [data-operate="delete"]').show();
                    } else {
                        $('[data-operate="permission"], [data-operate="unhealthy"], [data-operate="healthy"], [data-operate="delete"]').show();
                    }
                } else {
                    $('#selection-info').text('选择图片进行批量操作');
                    $('[data-operate]').hide();
                }

                // 更新选中样式
                $(IMAGES_ITEM).removeClass('border-blue-500 border-2');
                $(selected).addClass('border-blue-500 border-2');

                // 更新选择器图标
                $(IMAGES_ITEM).find('.image-selector i').removeClass('text-blue-500').addClass('text-gray-400');
                $(selected).find('.image-selector i').removeClass('text-gray-400').addClass('text-blue-500');
            }

            // 全选
            $('#select-all').click(function() {
                if (ds) {
                    ds.setSelection($(IMAGES_ITEM));
                    updateSelection();
                }
            });

            // 清空选择
            $('#clear-selection').click(function() {
                if (ds) {
                    ds.clearSelection();
                    updateSelection();
                }
            });

            // 批量操作方法
            const batchMethods = {
                permission() {
                    let selected = ds.getSelection().map(item => $(item).data('id'));
                    if (selected.length === 0) {
                        toastr.warning('请先选择图片');
                        return;
                    }
                    
                    Swal.fire({
                        title: '设置权限',
                        input: 'select',
                        inputOptions: {
                            '1': '公开',
                            '2': '私有'
                        },
                        inputPlaceholder: '选择权限类型',
                        showCancelButton: true,
                        confirmButtonText: '确认',
                        cancelButtonText: '取消',
                        inputValidator: (value) => {
                            if (!value) {
                                return '请选择权限类型'
                            }
                        }
                    }).then((result) => {
                        if (result.isConfirmed) {
                            axios.put('/admin/images/batch-permission', {
                                ids: selected,
                                permission: result.value
                            }).then(response => {
                                if (response.data.status) {
                                    toastr.success(response.data.message);
                                    setTimeout(() => location.reload(), 1000);
                                } else {
                                    toastr.error(response.data.message);
                                }
                            });
                        }
                    });
                },
                unhealthy() {
                    let selected = ds.getSelection().map(item => $(item).data('id'));
                    if (selected.length === 0) {
                        toastr.warning('请先选择图片');
                        return;
                    }
                    
                    Swal.fire({
                        title: '确认标记为违规?',
                        text: `将标记 ${selected.length} 张图片为违规内容`,
                        icon: 'warning',
                        showCancelButton: true,
                        confirmButtonText: '确认',
                        cancelButtonText: '取消'
                    }).then((result) => {
                        if (result.isConfirmed) {
                            axios.put('/admin/images/batch-unhealthy', {
                                ids: selected,
                                is_unhealthy: true
                            }).then(response => {
                                if (response.data.status) {
                                    toastr.success(response.data.message);
                                    setTimeout(() => location.reload(), 1000);
                                } else {
                                    toastr.error(response.data.message);
                                }
                            });
                        }
                    });
                },
                healthy() {
                    let selected = ds.getSelection().map(item => $(item).data('id'));
                    if (selected.length === 0) {
                        toastr.warning('请先选择图片');
                        return;
                    }
                    
                    Swal.fire({
                        title: '确认取消违规标记?',
                        text: `将取消 ${selected.length} 张图片的违规标记`,
                        icon: 'question',
                        showCancelButton: true,
                        confirmButtonText: '确认',
                        cancelButtonText: '取消'
                    }).then((result) => {
                        if (result.isConfirmed) {
                            axios.put('/admin/images/batch-unhealthy', {
                                ids: selected,
                                is_unhealthy: false
                            }).then(response => {
                                if (response.data.status) {
                                    toastr.success(response.data.message);
                                    setTimeout(() => location.reload(), 1000);
                                } else {
                                    toastr.error(response.data.message);
                                }
                            });
                        }
                    });
                },
                delete() {
                    let selected = ds.getSelection().map(item => $(item).data('id'));
                    if (selected.length === 0) {
                        toastr.warning('请先选择图片');
                        return;
                    }
                    
                    Swal.fire({
                        title: '确认删除?',
                        text: `将删除 ${selected.length} 张图片，包括记录与物理文件`,
                        icon: 'warning',
                        showCancelButton: true,
                        confirmButtonText: '确认删除',
                        cancelButtonText: '取消',
                        confirmButtonColor: '#d33'
                    }).then((result) => {
                        if (result.isConfirmed) {
                            axios.post('/admin/images/batch-delete', {
                                ids: selected
                            }).then(response => {
                                if (response.data.status) {
                                    toastr.success(response.data.message);
                                    setTimeout(() => location.reload(), 1000);
                                } else {
                                    toastr.error(response.data.message);
                                }
                            });
                        }
                    });
                }
            };

            // 绑定批量操作按钮
            $('[data-operate]').click(function() {
                let operate = $(this).data('operate');
                if (batchMethods[operate]) {
                    batchMethods[operate]();
                }
            });

            // 键盘快捷键
            $(document).keydown(e => {
                if (e.keyCode === 65 && (e.altKey || e.metaKey)) {
                    e.preventDefault();
                    if (ds) {
                        ds.setSelection($(IMAGES_ITEM));
                        updateSelection();
                    }
                }
            });

            // 页面加载完成后初始化
            $(document).ready(function() {
                initDragSelect();
                initContextMenu();
            });

            // 初始化右键菜单
            function initContextMenu() {
                context.init({
                    fadeSpeed: 100,
                    filter: function ($obj) {},
                    above: 'auto',
                    preventDoubleContext: true,
                    compress: false
                });

                // 右键菜单操作
                const contextActions = {
                    detail: {
                        text: '查看详情',
                        action: function(item) {
                            $(item).trigger('click');
                        },
                        visible: () => ds && ds.getSelection().length === 1
                    },
                    permission: {
                        text: '设置权限',
                        action: () => batchMethods.permission(),
                        visible: () => ds && ds.getSelection().length >= 1
                    },
                    unhealthy: {
                        text: '标记违规',
                        action: () => batchMethods.unhealthy(),
                        visible: () => ds && ds.getSelection().length >= 1
                    },
                    healthy: {
                        text: '取消违规',
                        action: () => batchMethods.healthy(),
                        visible: () => ds && ds.getSelection().length >= 1
                    },
                    delete: {
                        text: '删除',
                        action: () => batchMethods.delete(),
                        visible: () => ds && ds.getSelection().length >= 1
                    },
                    selectAll: {
                        text: '全选',
                        action: function() {
                            if (ds) {
                                ds.setSelection($(IMAGES_ITEM));
                                updateSelection();
                            }
                        },
                        visible: () => true
                    },
                    clearSelection: {
                        text: '清空选择',
                        action: function() {
                            if (ds) {
                                ds.clearSelection();
                                updateSelection();
                            }
                        },
                        visible: () => ds && ds.getSelection().length > 0
                    }
                };

                // 绑定容器右键菜单
                context.attach(IMAGES_CONTAINER, {
                    data: [
                        contextActions.selectAll,
                        contextActions.clearSelection
                    ],
                    beforeOpen: function() {
                        if (ds) {
                            ds.clearSelection();
                            updateSelection();
                        }
                    }
                });

                // 绑定图片项右键菜单
                context.attach(IMAGES_ITEM, {
                    data: [
                        {header: '图片操作'},
                        contextActions.detail,
                        contextActions.permission,
                        contextActions.unhealthy,
                        contextActions.healthy,
                        {divider: true},
                        contextActions.delete,
                        {divider: true},
                        contextActions.selectAll,
                        contextActions.clearSelection
                    ],
                    beforeOpen: function (item) {
                        // 如果当前项目未被选中，则选中它
                        if (ds && ds.getSelection().indexOf(item) === -1) {
                            if (ds.getSelection().length <= 1) {
                                ds.clearSelection();
                            }
                            ds.addSelection($(item));
                            updateSelection();
                        }
                    }
                });
            }

            function del(id) {
                Swal.fire({
                    title: `确认删除该图片吗?`,
                    text: "记录与物理文件将会一起删除。",
                    icon: 'warning',
                    showCancelButton: true,
                    confirmButtonText: '确认删除',
                }).then((result) => {
                    if (result.isConfirmed) {
                        axios.delete(`/admin/images/${id}`).then(response => {
                            if (response.data.status) {
                                modal.close('content-modal')
                                toastr.success(response.data.message);
                                setTimeout(function () {
                                    history.go(0);
                                }, 1000);
                            } else {
                                toastr.error(response.data.message);
                            }
                        });
                    }
                });
            }

            $('#grammar').click(function () {
                $('#modal-content').html($('#search-grammar-tpl').html());
                modal.open('content-modal')
            });

            // 批量选择模式状态
            let batchMode = false;
            let selectedIds = new Set();

            // 切换批量选择模式
            function toggleBatchMode(enable) {
                batchMode = enable;
                if (enable) {
                    $('#batch-control').removeClass('hidden');
                    $('.batch-checkbox-static').removeClass('hidden');
                    $('.batch-checkbox').addClass('hidden');
                    
                    // 同步静态复选框的状态
                    $('.item').each(function () {
                        let id = $(this).data('id');
                        let isChecked = selectedIds.has(id);
                        $(this).find('.batch-checkbox-static .image-checkbox').prop('checked', isChecked);
                        
                        // 应用视觉反馈
                        let item = $(this);
                        if (isChecked) {
                            item.addClass('ring-2 ring-blue-500 ring-offset-2 ring-offset-white');
                            item.css('opacity', '0.8');
                        } else {
                            item.removeClass('ring-2 ring-blue-500 ring-offset-2 ring-offset-white');
                            item.css('opacity', '1');
                        }
                    });
                } else {
                    $('#batch-control').addClass('hidden');
                    $('.batch-checkbox-static').addClass('hidden');
                    $('.batch-checkbox').removeClass('hidden');
                    selectedIds.clear();
                    $('.image-checkbox').prop('checked', false);
                    // 清除所有视觉反馈
                    $('.item').removeClass('ring-2 ring-blue-500 ring-offset-2 ring-offset-white').css('opacity', '1');
                    updateSelectedCount();
                }
            }

            // 更新选择数量显示
            function updateSelectedCount() {
                $('#selected-count').text(selectedIds.size);
                if (selectedIds.size > 0) {
                    $('#batch-control').removeClass('hidden');
                }
                
                // 添加动画效果
                if (selectedIds.size > 0) {
                    $('#selected-count').addClass('animate-pulse');
                    setTimeout(() => $('#selected-count').removeClass('animate-pulse'), 500);
                }
            }

            // 点击图片项
            $('.item').click(function (e) {
                // 如果点击的是复选框，不执行此事件
                if ($(e.target).is('input[type="checkbox"]')) {
                    return;
                }

                // 如果批量模式开启，切换复选框状态
                if (batchMode) {
                    let checkbox = $(this).find('.image-checkbox');
                    let wasChecked = checkbox.prop('checked');
                    checkbox.prop('checked', !wasChecked);
                    let id = $(this).data('id');
                    let item = $(this);
                    
                    if (!wasChecked) {
                        selectedIds.add(id);
                        // 立即应用视觉反馈
                        item.addClass('ring-2 ring-blue-500 ring-offset-2 ring-offset-white');
                        item.css('opacity', '0.8');
                    } else {
                        selectedIds.delete(id);
                        // 立即移除视觉反馈
                        item.removeClass('ring-2 ring-blue-500 ring-offset-2 ring-offset-white');
                        item.css('opacity', '1');
                    }
                    updateSelectedCount();
                    return;
                }

                let image = $(this).data('json');
                let previewUrl = ['psd', 'tif'].indexOf(image.extension) === -1 ? image.url : image.thumb_url;
                let html = $('#image-tpl').html()
                    .replace(/__id__/g, image.id)
                    .replace(/__url__/g, previewUrl)
                    .replace(/__user_name__/g, image.user ? image.user.name+'('+image.user.email+')' : '游客')
                    .replace(/__user_email__/g, image.user ? image.user.email : '-')
                    .replace(/__album_name__/g, image.album ? image.album.name : '-')
                    .replace(/__group_name__/g, image.group ? image.group.name : '-')
                    .replace(/__strategy_name__/g, image.strategy ? image.strategy.name : '-')
                    .replace(/__name__/g, image.name)
                    .replace(/__origin_name__/g, image.origin_name)
                    .replace(/__pathname__/g, image.pathname)
                    .replace(/__size__/g, utils.formatSize(image.size * 1024))
                    .replace(/__mimetype__/g, image.mimetype)
                    .replace(/__md5__/g, image.md5)
                    .replace(/__sha1__/g, image.sha1)
                    .replace(/__width__/g, image.width)
                    .replace(/__height__/g, image.height)
                    .replace(/__permission__/g, image.permission === {{ \App\Enums\ImagePermission::Public }} ? '<i class="fas fa-eye text-red-500"></i> 公开' : '<i class="fas fa-eye-slash text-green-500"></i> 私有')
                    .replace(/__is_unhealthy__/g, image.is_unhealthy ? '<span class="text-red-500"><i class="fas fa-exclamation-triangle"></i> 是</span>' : '否')
                    .replace(/__uploaded_ip__/g, image.uploaded_ip)
                    .replace(/__created_at__/g, image.created_at);

                $('#modal-content').html(html);

                modal.open('content-modal')
            });

            $('.item-user').click(function (e) {
                e.stopPropagation();
                let user = $(this).closest('.item').data('json').user || {};
                let html = $('#user-tpl').html()
                    .replace(/__avatar__/g, user.avatar)
                    .replace(/__name__/g, user.name)
                    .replace(/__email__/g, user.email)
                    .replace(/__capacity__/g, utils.formatSize(user.capacity * 1024))
                    .replace(/__used_capacity__/g, utils.formatSize(user.images_sum_size * 1024))
                    .replace(/__image_num__/g, user.image_num)
                    .replace(/__album_num__/g, user.album_num)
                    .replace(/__registered_ip__/g, user.registered_ip || '-')
                    .replace(/__status__/g, user.status === 1 ? '<span class="text-green-500">正常</span>' : '<span class="text-red-500">冻结</span>')
                    .replace(/__email_verified_at__/g, user.email_verified_at || '-')
                    .replace(/__created_at__/g, user.created_at);

                $('#modal-content').html(html);

                modal.open('content-modal')
            });

            // 复选框点击事件
            $(document).on('change', '.image-checkbox', function (e) {
                e.stopPropagation();
                let id = $(this).data('id');
                let isChecked = $(this).prop('checked');
                let item = $(this).closest('.item');
                
                // 同步两个复选框的状态
                let otherCheckbox = item.find('.image-checkbox').not(this);
                otherCheckbox.prop('checked', isChecked);
                
                if (isChecked) {
                    selectedIds.add(id);
                    // 如果还没有开启批量模式，则开启
                    if (!batchMode) {
                        batchMode = true;
                        $('#batch-control').removeClass('hidden');
                        $('.batch-checkbox-static').removeClass('hidden');
                        $('.batch-checkbox').addClass('hidden');
                    }
                    
                    // 立即应用视觉反馈
                    item.addClass('ring-2 ring-blue-500 ring-offset-2 ring-offset-white');
                    item.css('opacity', '0.8');
                } else {
                    selectedIds.delete(id);
                    
                    // 立即移除视觉反馈
                    item.removeClass('ring-2 ring-blue-500 ring-offset-2 ring-offset-white');
                    item.css('opacity', '1');
                }
                updateSelectedCount();
            });

            // 全选
            $('#select-all').click(function () {
                $('.image-checkbox').prop('checked', true);
                selectedIds.clear();
                $('.item').each(function () {
                    let id = $(this).data('id');
                    selectedIds.add(id);
                    // 应用视觉反馈
                    $(this).addClass('ring-2 ring-blue-500 ring-offset-2 ring-offset-white');
                    $(this).css('opacity', '0.8');
                });
                updateSelectedCount();
            });

            // 取消全选
            $('#select-none').click(function () {
                $('.image-checkbox').prop('checked', false);
                selectedIds.clear();
                // 移除所有视觉反馈
                $('.item').removeClass('ring-2 ring-blue-500 ring-offset-2 ring-offset-white').css('opacity', '1');
                updateSelectedCount();
            });

            // 反选
            $('#select-reverse').click(function () {
                $('.item').each(function () {
                    let checkbox = $(this).find('.image-checkbox');
                    let id = $(this).data('id');
                    let wasChecked = checkbox.prop('checked');
                    checkbox.prop('checked', !wasChecked);
                    
                    if (!wasChecked) {
                        selectedIds.add(id);
                        // 应用视觉反馈
                        $(this).addClass('ring-2 ring-blue-500 ring-offset-2 ring-offset-white');
                        $(this).css('opacity', '0.8');
                    } else {
                        selectedIds.delete(id);
                        // 移除视觉反馈
                        $(this).removeClass('ring-2 ring-blue-500 ring-offset-2 ring-offset-white');
                        $(this).css('opacity', '1');
                    }
                });
                updateSelectedCount();
            });

            // 批量删除
            $('#batch-delete').click(function () {
                if (selectedIds.size === 0) {
                    toastr.warning('请先选择要删除的图片');
                    return;
                }

                Swal.fire({
                    title: `确认删除选中的 ${selectedIds.size} 张图片吗?`,
                    html: `记录与物理文件将会一起删除，此操作<b style="color: #dc2626">不可恢复</b>！<br><br>已选择图片数量：<b>${selectedIds.size}</b>`,
                    icon: 'warning',
                    showCancelButton: true,
                    confirmButtonText: '确认删除',
                    cancelButtonText: '取消',
                    confirmButtonColor: '#dc2626',
                    cancelButtonColor: '#6b7280',
                    showLoaderOnConfirm: true,
                    preConfirm: () => {
                        // 显示加载状态
                        Swal.showLoading();
                        
                        return axios.post('/admin/images/batch-delete', {
                            ids: Array.from(selectedIds)
                        }).then(response => {
                            return response.data;
                        }).catch(error => {
                            Swal.showValidationMessage(
                                error.response?.data?.message || '删除失败，请重试'
                            );
                        });
                    },
                    allowOutsideClick: () => !Swal.isLoading()
                }).then((result) => {
                    if (result.isConfirmed && result.value) {
                        if (result.value.status) {
                            toastr.success(result.value.message);
                            setTimeout(function () {
                                history.go(0);
                            }, 1000);
                        } else {
                            toastr.error(result.value.message);
                        }
                    }
                });
            });

            // 取消批量模式
            $('#batch-cancel').click(function () {
                toggleBatchMode(false);
            });

            // 右键点击开启批量模式
            $('.item').contextmenu(function (e) {
                e.preventDefault();
                if (!batchMode) {
                    toggleBatchMode(true);
                    // 自动选中当前项
                    let checkbox = $(this).find('.image-checkbox');
                    checkbox.prop('checked', true);
                    // 同步另一个复选框
                    checkbox.closest('.item').find('.image-checkbox').prop('checked', true);
                    let id = $(this).data('id');
                    selectedIds.add(id);
                    // 应用视觉反馈
                    $(this).addClass('ring-2 ring-blue-500 ring-offset-2 ring-offset-white');
                    $(this).css('opacity', '0.8');
                    updateSelectedCount();
                }
            });

            // 键盘快捷键支持
            $(document).keydown(function (e) {
                // Ctrl + A 全选
                if (e.ctrlKey && e.key === 'a') {
                    e.preventDefault();
                    if (!batchMode) {
                        toggleBatchMode(true);
                    }
                    $('#select-all').click();
                }
                // Delete 键删除选中
                if (e.key === 'Delete' && selectedIds.size > 0) {
                    e.preventDefault();
                    $('#batch-delete').click();
                }
                // Escape 键取消批量模式
                if (e.key === 'Escape' && batchMode) {
                    toggleBatchMode(false);
                }
            });

            // Ctrl + 点击支持多选
            $('.item').mousedown(function (e) {
                if (e.ctrlKey && e.which === 1) { // 左键 + Ctrl
                    e.preventDefault();
                    if (!batchMode) {
                        toggleBatchMode(true);
                    }
                    let checkbox = $(this).find('.image-checkbox');
                    let wasChecked = checkbox.prop('checked');
                    checkbox.prop('checked', !wasChecked);
                    // 同步另一个复选框
                    checkbox.closest('.item').find('.image-checkbox').prop('checked', !wasChecked);
                    
                    let id = $(this).data('id');
                    let item = $(this);
                    
                    if (!wasChecked) {
                        selectedIds.add(id);
                        // 应用视觉反馈
                        item.addClass('ring-2 ring-blue-500 ring-offset-2 ring-offset-white');
                        item.css('opacity', '0.8');
                    } else {
                        selectedIds.delete(id);
                        // 移除视觉反馈
                        item.removeClass('ring-2 ring-blue-500 ring-offset-2 ring-offset-white');
                        item.css('opacity', '1');
                    }
                    updateSelectedCount();
                    return false;
                }
            });

            // 原有的单个删除功能（在非批量模式下保持可用）
            $('.item .delete').click(function (e) {
                e.stopPropagation();
                if (!batchMode) {
                    del($(this).data('id'));
                }
            });

            $('#modal-content').on('click', '.delete', function (e) {
                if (!batchMode) {
                    del($(this).data('id'));
                }
            });



        </script>
    @endpush
</x-app-layout>

<!-- Sidebar for desktop -->
<aside class="w-64 bg-white shadow-md hidden md:block h-screen sticky top-0">
    <div class="flex items-center gap-3 text-primary font-bold text-xl p-6 border-b">
        <i class="fas fa-building text-2xl"></i>
        <span>KollegieAdmin</span>
    </div>
    <div class="py-4">
        <div class="px-6 py-3 mb-4">
            <div class="flex items-center gap-3 mb-1">
                <div class="w-10 h-10 rounded-full bg-primary text-white flex items-center justify-center">
                    <span class="font-medium">AJ</span>
                </div>
                <div>
                    <p class="font-medium">Admin Jensen</p>
                    <p class="text-xs text-gray-500">Administrator</p>
                </div>
            </div>
        </div>
        <ul class="space-y-1">
            <li>
                <a href="index.html" class="flex items-center gap-3 px-6 py-3 bg-primary/10 text-primary font-medium border-r-4 border-primary">
                    <i class="fas fa-home"></i>
                    <span>Dashboard</span>
                </a>
            </li>
            <li>
                <a href="foodplan/index.html" class="flex items-center gap-3 px-6 py-3 text-gray-700 hover:bg-gray-100 transition-colors">
                    <i class="fas fa-utensils"></i>
                    <span>Madplan</span>
                </a>
            </li>
            <li>
                <a href="events/index.html" class="flex items-center gap-3 px-6 py-3 text-gray-700 hover:bg-gray-100 transition-colors">
                    <i class="fas fa-calendar-alt"></i>
                    <span>Begivenheder</span>
                    <span class="ml-auto bg-primary text-white text-xs px-2 py-1 rounded-full">3</span>
                </a>
            </li>
            <li>
                <a href="news/index.html" class="flex items-center gap-3 px-6 py-3 text-gray-700 hover:bg-gray-100 transition-colors">
                    <i class="fas fa-newspaper"></i>
                    <span>Nyheder</span>
                </a>
            </li>
            <li>
                <a href="#" class="flex items-center gap-3 px-6 py-3 text-gray-700 hover:bg-gray-100 transition-colors">
                    <i class="fas fa-users"></i>
                    <span>Beboere</span>
                </a>
            </li>
            <li>
                <a href="#" class="flex items-center gap-3 px-6 py-3 text-gray-700 hover:bg-gray-100 transition-colors">
                    <i class="fas fa-cog"></i>
                    <span>Indstillinger</span>
                </a>
            </li>
        </ul>
    </div>
    <div class="absolute bottom-0 w-full p-6 border-t">
        <a href="#" class="flex items-center gap-3 text-gray-700 hover:text-danger transition-colors">
            <i class="fas fa-sign-out-alt"></i>
            <span>Log ud</span>
        </a>
    </div>
</aside>

<!-- Mobile sidebar menu (hidden by default) -->
<div id="mobile-sidebar" class="fixed inset-0 bg-black bg-opacity-50 z-50 hidden">
    <div class="bg-white w-64 h-full overflow-y-auto">
        <div class="flex items-center justify-between p-4 border-b">
            <div class="flex items-center gap-3 text-primary font-bold text-xl">
                <i class="fas fa-building text-2xl"></i>
                <span>KollegieAdmin</span>
            </div>
            <button id="close-mobile-menu" class="text-gray-700">
                <i class="fas fa-times text-xl"></i>
            </button>
        </div>
        <div class="py-4">
            <div class="px-4 py-3 mb-4">
                <div class="flex items-center gap-3 mb-1">
                    <div class="w-10 h-10 rounded-full bg-primary text-white flex items-center justify-center">
                        <span class="font-medium">AJ</span>
                    </div>
                    <div>
                        <p class="font-medium">Admin Jensen</p>
                        <p class="text-xs text-gray-500">Administrator</p>
                    </div>
                </div>
            </div>
            <ul class="space-y-1">
                <li>
                    <a href="index.html" class="flex items-center gap-3 px-4 py-3 bg-primary/10 text-primary font-medium border-l-4 border-primary">
                        <i class="fas fa-home"></i>
                        <span>Dashboard</span>
                    </a>
                </li>
                <li>
                    <a href="foodplan/index.html" class="flex items-center gap-3 px-4 py-3 text-gray-700 hover:bg-gray-100 transition-colors">
                        <i class="fas fa-utensils"></i>
                        <span>Madplan</span>
                    </a>
                </li>
                <li>
                    <a href="events/index.html" class="flex items-center gap-3 px-4 py-3 text-gray-700 hover:bg-gray-100 transition-colors">
                        <i class="fas fa-calendar-alt"></i>
                        <span>Begivenheder</span>
                        <span class="ml-auto bg-primary text-white text-xs px-2 py-1 rounded-full">3</span>
                    </a>
                </li>
                <li>
                    <a href="news/index.html" class="flex items-center gap-3 px-4 py-3 text-gray-700 hover:bg-gray-100 transition-colors">
                        <i class="fas fa-newspaper"></i>
                        <span>Nyheder</span>
                    </a>
                </li>
                <li>
                    <a href="#" class="flex items-center gap-3 px-4 py-3 text-gray-700 hover:bg-gray-100 transition-colors">
                        <i class="fas fa-users"></i>
                        <span>Beboere</span>
                    </a>
                </li>
                <li>
                    <a href="#" class="flex items-center gap-3 px-4 py-3 text-gray-700 hover:bg-gray-100 transition-colors">
                        <i class="fas fa-cog"></i>
                        <span>Indstillinger</span>
                    </a>
                </li>
            </ul>
        </div>
        <div class="absolute bottom-0 w-full p-6 border-t">
            <a href="#" class="flex items-center gap-3 text-gray-700 hover:text-danger transition-colors">
                <i class="fas fa-sign-out-alt"></i>
                <span>Log ud</span>
            </a>
        </div>
    </div>
</div>
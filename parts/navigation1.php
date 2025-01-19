<!-- navigation1.php -->
<nav class="navbar">
    <div class="navbar-container">
        <a href="http://localhost/void/PublicPost.php" class="navbar-brand">
            <span class="void-text">VO!D</span>
        </a>
        <!-- Navbar Links -->
        <ul class="navbar-links">
            <li><a onclick="PublicPost()">Global Feed</a></li>
            <li><a onclick="FriendPost()">Local Feed</a></li>
            <li><a onclick="Post()">Post</a></li>

            <!-- Friends Dropdown -->
            <li class="dropdown">
                <a href="#" class="dropbtn" onclick="toggleDropdown()">Friends</a>
                <ul class="dropdown-content" id="friendsDropdown">
                    <li><a onclick="friend_list()">Friends</a></li>
                    <li><a onclick="view_requests()">Friend requests</a></li>
                    <li><a onclick="Search_users()">Friend search</a></li>
                </ul>
            </li>

            <li><a onclick="me()">Me</a></li>
            <li><a href="http://localhost/void/login/logout.php">Logout</a></li>
        </ul>


        <form class="navbar-search" action="http://localhost/void/features/navsearch.php" method="GET">
            <input type="text" name="search_term" placeholder="Search..." class="search-input" required>
            <button type="submit" class="search-btn">Search</button>
        </form>


    </div>
</nav>

<!-- JavaScript to toggle the dropdown -->
<script>
    function toggleDropdown() {
        var dropdown = document.getElementById("friendsDropdown");
        if (dropdown.style.display === "block") {
            dropdown.style.display = "none";
        } else {
            dropdown.style.display = "block";
        }
    }


    function friend_list() {
        window.location.href = "http://localhost/void/features/friend_list.php";
    }

    function view_requests() {
        window.location.href = "http://localhost/void/features/view_requests.php";
    }

    function Search_users() {
        window.location.href = "http://localhost/void/features/Search_users.php";
    }

    function Post() {
        window.location.href = "http://localhost/void/features/post.php";
    }

    function FriendPost() {
        window.location.href = "http://localhost/void/FriendPost.php";
    }

    function PublicPost() {
        window.location.href = "http://localhost/void/PublicPost.php";
    }

    function me() {
        window.location.href = "http://localhost/void/me.php";
    }

    function voidx() {
        window.location.href = "http://localhost/void/PublicPost.php";
    }

    function logout() {
        window.location.href = "http://localhost/void/login/logout.php";
    }
</script>

<style>
    .void-text {
        font-size: 36px;
        color: white;
        text-shadow: 0 0 8px rgba(255, 255, 255, 0.6);
        font-weight: bold;
        text-decoration: none;
        cursor: pointer;
    }


    .void-text:hover {
        text-shadow: 0 0 12px rgba(255, 255, 255, 0.8);
        color: #e0e0e0;
    }


    .navbar-brand li {
        list-style-type: none;
        margin: 0;
        padding: 0;
    }

    .dropdown {
        position: relative;
        display: inline-block;
    }

    .dropbtn {
        background-color: transparent;
        border: none;
        cursor: pointer;
        font-size: 16px;
    }

    .dropdown-content {
        display: none;
        position: absolute;
        background-color: #f9f9f9;
        min-width: 160px;
        box-shadow: 0px 8px 16px rgba(0, 0, 0, 0.2);
        z-index: 1;
    }

    .dropdown-content li {
        padding: 12px 16px;
        list-style-type: none;
    }

    .dropdown-content li a {
        text-decoration: none;
        color: black;
        display: block;
        cursor: pointer;

    }

    .dropdown-content li a:hover {
        color: green;
        background-color: #f9f9f9;
    }

    .dropdown-content li :hover {
        color: #07f527;
        background-color: white;
        cursor: pointer;
    }

    .dropdown:hover .dropdown-content {
        display: block;
    }

    .navbar-links {
        cursor: pointer;
    }
</style>
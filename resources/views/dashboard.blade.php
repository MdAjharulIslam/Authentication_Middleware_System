
<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Dashboard</title>

    <!-- Bootstrap CSS -->
    <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0/dist/css/bootstrap.min.css" rel="stylesheet">

    <style>
        body {
            overflow-x: hidden;
        }
        .sidebar {
            height: 100vh;
            background: #343a40;
            color: white;
        }
        .sidebar a {
            color: white;
            text-decoration: none;
            display: block;
            padding: 10px;
        }
        .sidebar a:hover {
            background: #495057;
        }
    </style>
</head>
<body>

<div class="container-fluid">
    <div class="row">

        <!-- Sidebar -->
        <div class="col-md-2 sidebar p-3">
            <h4 class="text-center">My App</h4>
            <hr>

            <a href="#">🏠 Dashboard</a>
            <a href="#">👤 Profile</a>
            <a href="#">📦 Orders</a>
            <a href="#">⚙️ Settings</a>
            <a href="#" id="logout">🚪 Logout</a>
        </div>

        <!-- Main Content -->
        <div class="col-md-10">

            <!-- Navbar -->
            <nav class="navbar navbar-light bg-light shadow-sm mb-3">
                <div class="container-fluid">
                    <span class="navbar-brand mb-0 h5">Dashboard</span>
                    <span>Welcome, User 👋</span>
                </div>
            </nav>

            <!-- Cards -->
            <div class="row">

                <div class="col-md-4">
                    <div class="card text-bg-primary mb-3">
                        <div class="card-body">
                            <h5>Total Users</h5>
                            <h3>120</h3>
                        </div>
                    </div>
                </div>

                <div class="col-md-4">
                    <div class="card text-bg-success mb-3">
                        <div class="card-body">
                            <h5>Total Orders</h5>
                            <h3>75</h3>
                        </div>
                    </div>
                </div>

                <div class="col-md-4">
                    <div class="card text-bg-warning mb-3">
                        <div class="card-body">
                            <h5>Revenue</h5>
                            <h3>$1,200</h3>
                        </div>
                    </div>
                </div>

            </div>

            <!-- Table -->
            <div class="card mt-3">
                <div class="card-header">
                    Recent Orders
                </div>
                <div class="card-body">

                    <table class="table table-bordered">
                        <thead>
                            <tr>
                                <th>#</th>
                                <th>User</th>
                                <th>Product</th>
                                <th>Status</th>
                            </tr>
                        </thead>
                        <tbody>
                            <tr>
                                <td>1</td>
                                <td>John</td>
                                <td>Phone</td>
                                <td><span class="badge bg-success">Completed</span></td>
                            </tr>
                            <tr>
                                <td>2</td>
                                <td>Ali</td>
                                <td>Laptop</td>
                                <td><span class="badge bg-warning">Pending</span></td>
                            </tr>
                        </tbody>
                    </table>

                </div>
            </div>

        </div>
        
        <form method="POST" action="{{route('logout')}}">
            @csrf
            <button>Logout</button>
        </form>

   <a href="">Go to inner</a>
    </div>
</div>

   

</body>
</html>


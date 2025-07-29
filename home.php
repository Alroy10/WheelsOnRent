<!-- <?php
session_start();

// Update database connection to use wheelsonrent database
$conn = new mysqli("localhost", "root", "", "wheelsonrent");

// Check connection
if ($conn->connect_error) {
    die("Connection failed: " . $conn->connect_error);
}
$profile_picture = "profile.png"; // Default image
if (isset($_SESSION['user_id'])) {
    $user_id = $_SESSION['user_id'];
    $sql = "SELECT profile_picture FROM users WHERE user_id = ?";
    $stmt = $conn->prepare($sql);
    $stmt->bind_param("i", $user_id);
    $stmt->execute();
    $result = $stmt->get_result();
    if ($user = $result->fetch_assoc()) {
        if (!empty($user['profile_picture'])) {
            $profile_picture = $user['profile_picture'];
        }
    }
}

// Handle form submission
if ($_SERVER["REQUEST_METHOD"] == "POST") {
    $location = $_POST['location'];
    $trip_start = $_POST['startDate'];
    $trip_end = $_POST['endDate'];
    $delivery_pickup = isset($_POST['delivery']) ? 1 : 0;

    $stmt = $conn->prepare("INSERT INTO trip_bookings (location, trip_start, trip_end, delivery_pickup) VALUES (?, ?, ?, ?)");
    $stmt->bind_param("sssi", $location, $trip_start, $trip_end, $delivery_pickup);

    if ($stmt->execute()) {
        header("Location: search-results.php");
        exit();
    } else {
        echo "<script>alert('Error: " . $stmt->error . "');</script>";
    }
   


    $stmt->close();
}

?> -->

<!DOCTYPE html>
<html lang="en">
  <head>
    <meta charset="UTF-8" />
    <meta name="viewport" content="width=device-width, initial-scale=1.0" />
    <title>WheelsOnRent - Rent Your Ride</title>
    <link rel="stylesheet" href="home-style.css" />
    <link
      rel="stylesheet"
      href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.5.1/css/all.min.css"
    />
    <link
      href="https://fonts.googleapis.com/css2?family=Poppins:wght@300;400;600;700&display=swap"
      rel="stylesheet"
    />
  </head>
  <body>
    <!-- Header -->
    <header>
      <div class="logo">
        <!--<img src="logo2.png" alt="Mahindra Scorpio" />-->
        <h1>WheelsOnRent</h1>
      </div>
      <nav>
        <ul>
          <li><a href="#" class="nav-link">Home</a></li>
          <li><a href="#vehicles" class="nav-link">Vehicles</a></li>
          <li><a href="#about" class="nav-link">About</a></li>
          <li><a href="#contact" class="nav-link">Contact</a></li>
          <li><a href="book.php" class="nav-btn">Book Now</a></li>
          <li class="profile-dropdown">
            <img
              src="<?php echo htmlspecialchars($profile_picture); ?>"
              alt="Profile"
              class="profile-icon"
            />
            <div class="dropdown-content">
              <a href="profile.php">Profile</a>
              <a href="login.php">Login</a>
              <a href="logout.php" id="signout" class="signout-link"
                >Sign Out</a
              >
            </div>
          </li>
        </ul>
        <div class="menu-toggle">
          <i class="fas fa-bars"></i>
        </div>
      </nav>
    </header>

    <!-- Hero Section -->
    <section class="hero">
      <div class="hero-backpanel"></div>
      <div class="hero-secbackpanel"></div>
      <div class="hero-content">
        <!-- Left Side: Search Bar -->
        <div class="search-section">
          <h2>Looking for vehicle rentals?</h2>
          <h1>Explore self-drive cars & bikes</h1>
          <div id="validation-message"></div>
          <div class="search-bar">
            <form id="search-form" method="POST" action="">
              <label>Location</label>
              <input
                type="text"
                name="location"
                placeholder="Enter city or location"
                required
              />
              <div class="date-section">
                <div>
                  <label>Trip Starts</label>
                  <input type="date" name="startDate" required />
                </div>
                <div>
                  <label>Trip Ends</label>
                  <input type="date" name="endDate" required />
                </div>
              </div>
              <div class="checkbox-container">
                <input type="checkbox" id="delivery" name="delivery" />
                <label for="delivery" class="checkbox-label"
                  >Delivery & Pick-up from anywhere</label
                >
              </div>
              <button type="submit" class="search-btn">Search</button>
            </form>
          </div>
        </div>

        <!-- Right Side: Background Slides -->
        <div class="hero-background" id="hero-background">
          <div class="background-slide vehicle-info-slide">
            <div class="vehicle-info">
              <div class="info-item">
                <i class="fas fa-car"></i>
                <h3>High-quality car options</h3>
              </div>
              <div class="info-item">
                <i class="fas fa-infinity"></i>
                <h3>Unlimited KMs to drive and stop anywhere</h3>
              </div>
              <div class="info-item">
                <i class="fas fa-lock"></i>
                <h3>No security deposit on any booking</h3>
              </div>
              <div class="info-item">
                <i class="fas fa-shield-alt"></i>
                <h3>100% Trip protection for a safe, hassle-free drive</h3>
              </div>
              <div class="info-item">
                <i class="fas fa-headset"></i>
                <h3>24/7 customer support for dedicated assistance</h3>
              </div>
            </div>
          </div>
          <div class="background-slide drive-anytime-slide">
            <div class="drive-anytime">
              <h2>Drive Anytime, Anywhere</h2>
              <p>
                With no commitment, unlimited options and hassle-free booking,
                your road to adventure's just a WheelsOnRent away!
              </p>
            </div>
          </div>
          <div class="background-slide drive-anytime-slide">
            <div class="drive-anytimes">
              <img src="vehicle.png" />
            </div>
          </div>
        </div>
      </div>
      <div class="nav-dots">
        <span class="dot active" data-slide="0" id="one"></span>
        <span class="dot" data-slide="1"></span>
        <span class="dot" data-slide="2"></span>
      </div>
    </section>

    <!-- Vehicle Selection Section -->
    <section id="vehicles" class="vehicle-section">
      <h2>Explore Our Top Liked Vehicles</h2>
      <div class="explore-nav">
        <button class="explore-nav-btn prev">
          <i class="fas fa-chevron-left"></i>
        </button>
        <button class="explore-nav-btn next">
          <i class="fas fa-chevron-right"></i>
        </button>
      </div>
      <div class="vehicle-container">
        <div class="vehicle-item">
          <div class="vehicle-image">
            <img src="vehicle_images/SUV/scropio.jpg" alt="Mahindra Scorpio" />
          </div>
          <div class="vehicle-details">
            <div class="vehicle-name">
              <h3>Mahindra Scorpio</h3>
              <span class="vehicle-type">SUV</span>
            </div>
            <div class="specs">
              <span><i class="fas fa-user"></i> 7 Seats</span>
              <span><i class="fas fa-gas-pump"></i> Diesel</span>
              <span><i class="fas fa-cog"></i> Manual</span>
            </div>
            <div class="price-booking">
              <div class="price">
                <span class="rupee">₹</span>
                <span class="amount">3000</span>
                <span class="period">/day</span>
              </div>
              <a href="book.php" class="booking-arrow">
                <img src="orange_arrow.png" alt="Book Now" class="arrow-icon" />
              </a>
            </div>
          </div>
        </div>
        <div class="vehicle-item">
          <div class="vehicle-image">
            <img src="vehicle_images/Scooty/Activa" alt="Aciva" />
          </div>
          <div class="vehicle-details">
            <div class="vehicle-name">
              <h3>Honda Activa</h3>
              <span class="vehicle-type">Scooter</span>
            </div>
            <div class="specs">
              <span><i class="fas fa-user"></i>2 Seats</span>
              <span><i class="fas fa-gas-pump"></i>Petrol</span>
            </div>
            <div class="price-booking">
              <div class="price">
                <span class="rupee">₹</span>
                <span class="amount">400</span>
                <span class="period">/day</span>
              </div>
              <a href="book.php" class="booking-arrow">
                <img src="orange_arrow.png" alt="Book Now" class="arrow-icon" />
              </a>
            </div>
          </div>
        </div>
        <div class="vehicle-item">
          <div class="vehicle-image">
            <img src="vehicle_images/MPV/innova.jpg" alt="Toyota innova" />
          </div>
          <div class="vehicle-details">
            <div class="vehicle-name">
              <h3>Toyota Innova</h3>
              <span class="vehicle-type">MPV</span>
            </div>
            <div class="specs">
              <span><i class="fas fa-user"></i>7 Seats</span>
              <span><i class="fas fa-gas-pump"></i>Diesel</span>
              <span><i class="fas fa-cog"></i>Manual</span>
            </div>
            <div class="price-booking">
              <div class="price">
                <span class="rupee">₹</span>
                <span class="amount">3000</span>
                <span class="period">/day</span>
              </div>
              <a href="book.php" class="booking-arrow">
                <img src="orange_arrow.png" alt="Book Now" class="arrow-icon" />
              </a>
            </div>
          </div>
        </div>
        <div class="vehicle-item">
          <div class="vehicle-image">
            <img src="vehicle_images/Bike/himalayan" alt="Himalayan" />
          </div>
          <div class="vehicle-details">
            <div class="vehicle-name">
              <h3>Royal Enfield Himalayan</h3>
              <span class="vehicle-type">Bike</span>
            </div>
            <div class="specs">
              <span><i class="fas fa-user"></i>2 Seats</span>
              <span><i class="fas fa-gas-pump"></i>Petrol</span>
            </div>
            <div class="price-booking">
              <div class="price">
                <span class="rupee">₹</span>
                <span class="amount">1000</span>
                <span class="period">/day</span>
              </div>
              <a href="book.php" class="booking-arrow">
                <img src="orange_arrow.png" alt="Book Now" class="arrow-icon" />
              </a>
            </div>
          </div>
        </div>
        <div class="vehicle-item">
          <div class="vehicle-image">
            <img
              src="vehicle_images/Sedan/virtus.jpg"
              alt="Volkswagen Virtus"
            />
          </div>
          <div class="vehicle-details">
            <div class="vehicle-name">
              <h3>Volkswagen Virtus</h3>
              <span class="vehicle-type">Sedan</span>
            </div>
            <div class="specs">
              <span><i class="fas fa-user"></i>5 Seats</span>
              <span><i class="fas fa-gas-pump"></i>Petrol</span>
              <span><i class="fas fa-cog"></i>Automatic</span>
            </div>
            <div class="price-booking">
              <div class="price">
                <span class="rupee">₹</span>
                <span class="amount">5000</span>
                <span class="period">/day</span>
              </div>
              <a href="book.php" class="booking-arrow">
                <img src="orange_arrow.png" alt="Book Now" class="arrow-icon" />
              </a>
            </div>
          </div>
        </div>
        <div class="vehicle-item">
          <div class="vehicle-image">
            <img src="vehicle_images/hatchback/ritz.jpg" alt="Maruti Ritz " />
          </div>
          <div class="vehicle-details">
            <div class="vehicle-name">
              <h3>Maruti Ritz</h3>
              <span class="vehicle-type">Hatchback</span>
            </div>
            <div class="specs">
              <span><i class="fas fa-user"></i>5 Seats</span>
              <span><i class="fas fa-gas-pump"></i>Diesel</span>
              <span><i class="fas fa-cog"></i>Manual</span>
            </div>
            <div class="price-booking">
              <div class="price">
                <span class="rupee">₹</span>
                <span class="amount">2000</span>
                <span class="period">/day</span>
              </div>
              <a href="book.php" class="booking-arrow">
                <img src="orange_arrow.png" alt="Book Now" class="arrow-icon" />
              </a>
            </div>
          </div>
        </div>
        <div class="vehicle-item">
          <div class="vehicle-image">
            <img src="vehicle_images/SUV/thar.jpg" alt="Mahindra Thar" />
          </div>
          <div class="vehicle-details">
            <div class="vehicle-name">
              <h3>Mahindra Thar</h3>
              <span class="vehicle-type">SUV</span>
            </div>
            <div class="specs">
              <span><i class="fas fa-user"></i>5 Seats</span>
              <span><i class="fas fa-gas-pump"></i>Petrol</span>
              <span><i class="fas fa-cog"></i>Automatic</span>
            </div>
            <div class="price-booking">
              <div class="price">
                <span class="rupee">₹</span>
                <span class="amount">2500</span>
                <span class="period">/day</span>
              </div>
              <a href="book.php" class="booking-arrow">
                <img src="orange_arrow.png" alt="Book Now" class="arrow-icon" />
              </a>
            </div>
          </div>
        </div>
        <div class="vehicle-item">
          <div class="vehicle-image">
            <img src="vehicle_images/hatchback/i20.jpg" alt="Hyundai i20" />
          </div>
          <div class="vehicle-details">
            <div class="vehicle-name">
              <h3>Hyundai i20</h3>
              <span class="vehicle-type">Hatchback</span>
            </div>
            <div class="specs">
              <span><i class="fas fa-user"></i>5 Seats</span>
              <span><i class="fas fa-gas-pump"></i>Petrol</span>
              <span><i class="fas fa-cog"></i>Automatic</span>
            </div>
            <div class="price-booking">
              <div class="price">
                <span class="rupee">₹</span>
                <span class="amount">2000</span>
                <span class="period">/day</span>
              </div>
              <a href="book.php" class="booking-arrow">
                <img src="orange_arrow.png" alt="Book Now" class="arrow-icon" />
              </a>
            </div>
          </div>
        </div>
      </div>
    </section>

    <!-- About Section -->
    <section id="about" class="about-section">
      <h2>Why Choose WheelsOnRent?</h2>
      <p>
        At WheelsOnRent, we make renting a vehicle easy and exciting. Choose
        from our wide range of cars and bikes, perfect for any journey—whether
        it's a city commute, a family trip, or an adventurous ride. We offer
        competitive pricing, seamless booking, and 24/7 customer support.
      </p>
    </section>

    <!-- Testimonials Section -->
    <section id="testimonials" class="testimonials-section">
      <div class="section-label">
        <span class="label-text">* Testimonials</span>
      </div>
      <h2>What Our Customers Are Saying About Us</h2>
      <div class="testimonials-container" id="testimonials-container">
        <div class="testimonial-slide">
          <div class="testimonial">
            <p>
              "The quality of the car provided was very good and the ease of
              communication before landing in Goa and after landing was nice."
            </p>
            <div class="testimonial-author">
              <img src="People/1.jpeg" alt="Akash Rajkumar" />
              <h4>Akash Rajkumar</h4>
            </div>
          </div>
          <div class="testimonial">
            <p>
              "The condition of the car was same as shown in the picture and the
              owner of the company was very friendly and helpful. The prices of
              vehicles are very cheap."
            </p>
            <div class="testimonial-author">
              <img src="People/2.jpeg" alt="Rajvansh Singh" />
              <h4>Rajvansh Singh</h4>
            </div>
          </div>
          <div class="testimonial">
            <p>
              "The car was in perfect condition and I completely enjoyed my
              drive. Returning the car was also hassle free. Highly recommend."
            </p>
            <div class="testimonial-author">
              <img src="People/4.jpg" alt="Vinay Kumar" />
              <h4>Vinay Kumar</h4>
            </div>
          </div>
        </div>
        <div class="testimonial-slide">
          <div class="testimonial">
            <p>
              "The booking process was smooth, and the bike was delivered on
              time. I had an amazing ride through the hills!"
            </p>
            <div class="testimonial-author">
              <img src="People/3.jpeg" alt="priya sharma " />
              <h4>Priya Sharma</h4>
            </div>
          </div>
          <div class="testimonial">
            <p>
              "Great experience! The car was clean, and the staff was very
              professional. Will definitely rent again."
            </p>
            <div class="testimonial-author">
              <img src="People/5.jpg" alt="Arjun Patel" />
              <h4>Arjun Patel</h4>
            </div>
          </div>
        </div>
      </div>
      <div class="testimonial-nav">
        <span class="arrow prev" data-direction="prev">&#10094;</span>
        <span class="arrow next" data-direction="next">&#10095;</span>
      </div>
      <div class="testimonial-dots">
        <span class="dot active" data-slide="0"></span>
        <span class="dot" data-slide="1"></span>
      </div>
    </section>

    <!-- FAQ Section -->
    <section id="faq" class="faq-section">
      <div class="section-label">
        <span class="label-text">* Frequently Asked Questions</span>
      </div>
      <div class="faq-content">
        <div class="faq-image">
          <img src="bmw.jpg" alt="Car with Scenic Background" />
        </div>
        <div class="faq-questions">
          <h2>Everything You Need to Know About Our Services</h2>
          <ul>
            <li class="faq-item">
              <div class="faq-question">
                <span>What Do I Need to Rent a Car?</span>
                <i class="fas fa-chevron-down"></i>
              </div>
              <div class="faq-answer">
                <p>
                  Explore our diverse selection of high-end vehicles, choose
                  your preferred pickup and return dates, and select a location
                  that best fits your needs.
                </p>
              </div>
            </li>
            <li class="faq-item">
              <div class="faq-question">
                <span>How Old Do I Need to Be to Rent a Car?</span>
                <i class="fas fa-chevron-down"></i>
              </div>
              <div class="faq-answer">
                <p>
                  You need to be at least 21 years old to rent a car with us.
                  However, drivers under 25 may be subject to a young driver
                  surcharge.
                </p>
              </div>
            </li>
            <li class="faq-item">
              <div class="faq-question">
                <span>Can I Rent a Car With a Debit Card?</span>
                <i class="fas fa-chevron-down"></i>
              </div>
              <div class="faq-answer">
                <p>
                  Yes, you can rent a car with a debit card. However, we may
                  require a hold on your account for the rental period as a
                  security deposit.
                </p>
              </div>
            </li>
          </ul>
        </div>
      </div>
    </section>

    <!-- Quick Links Footer -->
    <section id="contact" class="quick-links">
      <div class="links-container">
        <div class="link-column">
          <h3>Get to Know Us</h3>
          <ul>
            <li><a href="home.php#about">About Us</a></li>
            <li><a href="home.php#testimonials">Testimonials</a></li>
          </ul>
        </div>
        <div class="link-column">
          <h3>Rent With Us</h3>
          <ul>
            <li><a href="home.php#vehicles">Vehicles</a></li>
            <li><a href="insurance.php">Insurance & Protection</a></li>
          </ul>
        </div>
        <div class="link-column">
          <h3>Customer Service</h3>
          <ul>
            <li><a href="home.php#faq">FAQ</a></li>
            <li><a href="terms.php">Terms & Conditions</a></li>
            <li><a href="privacy.php">Privacy Policy</a></li>
          </ul>
        </div>

        <div class="link-column">
          <h3>Connect With Us</h3>
          <ul>
            <li><a href="contact.php">Contact Us</a></li>
            <li>
              <a href="mailto:info@wheelsonrent.com"
                >Email: info@wheelsonrent.com</a
              >
            </li>
            <li>Phone: +91 9923 668 188</li>
            <li class="social-links">
              <a href="#"><i class="fab fa-facebook"></i></a>
              <a href="#"><i class="fab fa-twitter"></i></a>
              <a href="#"><i class="fab fa-instagram"></i></a>
            </li>
          </ul>
        </div>
      </div>
    </section>

    <footer>
      <p>© 2025 WheelsOnRent. All rights reserved.</p>
    </footer>

    <script>
      // Smooth scrolling for in-page links
      document.querySelectorAll('a[href^="#"]').forEach((anchor) => {
        anchor.addEventListener("click", function (e) {
          e.preventDefault();
          const targetId = this.getAttribute("href").substring(1);
          const targetElement = document.getElementById(targetId);
          if (targetElement) {
            targetElement.scrollIntoView({ behavior: "smooth" });
          }
        });
      });

      // Set minimum date for date inputs to today
      const dateInputs = document.querySelectorAll('input[type="date"]');
      const today = new Date().toISOString().split("T")[0];
      dateInputs.forEach((input) => {
        input.min = today;
      });

      // Search form submission handling
      document
        .getElementById("search-form")
        .addEventListener("submit", function (e) {
          e.preventDefault();

          const location = this.querySelector('input[name="location"]').value;
          const startDate = this.querySelector('input[name="startDate"]').value;
          const endDate = this.querySelector('input[name="endDate"]').value;
          const delivery = this.querySelector('input[name="delivery"]').checked;
          const messageElement = document.getElementById("validation-message");

          // Function to show error message
          const showError = (message) => {
            messageElement.textContent = message;
            messageElement.classList.add("show");
            messageElement.scrollIntoView({
              behavior: "smooth",
              block: "center",
            });
          };

          // Get current date (without time)
          const today = new Date();
          today.setHours(0, 0, 0, 0);
          const selectedStartDate = new Date(startDate);
          selectedStartDate.setHours(0, 0, 0, 0);

          // Validate start date is not in the past
          if (selectedStartDate < today) {
            showError(
              "Trip Start Date cannot be in the past. Please select today or a future date."
            );
            return;
          }

          // Check if start and end dates are the same
          if (startDate === endDate) {
            showError(
              "Trip End Date cannot be the same as Start Date. Please select at least one day after."
            );
            return;
          }

          // Validate end date is after start date
          if (new Date(startDate) > new Date(endDate)) {
            showError("Trip End Date must be after the Start Date.");
            return;
          }

          // Clear any existing error message if validation passes
          messageElement.textContent = "";
          messageElement.classList.remove("show");

          // Submit the form if all validations pass
          this.submit();
        });

      // Mobile menu toggle
      const menuToggle = document.querySelector(".menu-toggle");
      const nav = document.querySelector("nav ul");
      menuToggle.addEventListener("click", () => {
        nav.classList.toggle("show");
      });

      // Background slideshow (Hero Section)
      const slides = document.querySelectorAll(".background-slide");
      const dots = document.querySelectorAll(".nav-dots .dot");
      const slideBack = document.querySelector(".hero-backpanel");
      const slideBacks = document.querySelector(".hero-secbackpanel");
      const buttonslide = document.getElementById("one");
      let currentSlide = 0;

      function showSlide(index) {
        slides.forEach((slide, i) => {
          slide.style.opacity = i === index ? "1" : "0";
          console.log(index);
        });
        dots.forEach((dot, i) => {
          dot.classList.toggle("active", i === index);
          switch (index) {
            case 0:
              slideBack.classList.remove("act");
              slideBack.classList.add("dact");
              slideBacks.classList.remove("act");
              slideBacks.classList.add("dact");
              break;

            case 1:
              slideBack.classList.remove("dact");
              slideBack.classList.add("act");
              slideBacks.classList.remove("act");
              slideBacks.classList.add("dact");
              break;

            case 2:
              slideBack.classList.remove("act");
              slideBack.classList.add("dact");
              slideBacks.classList.remove("dact");
              slideBacks.classList.add("act");
              break;

            default:
              console.log("error");
          }
        });
      }

      setInterval(() => {
        currentSlide = (currentSlide + 1) % slides.length;
        showSlide(currentSlide);
      }, 10000);

      dots.forEach((dot) => {
        dot.addEventListener("click", () => {
          currentSlide = parseInt(dot.getAttribute("data-slide"));
          showSlide(currentSlide);
        });
      });

      showSlide(currentSlide);

      // Testimonials slideshow
      const testimonialSlides = document.querySelectorAll(".testimonial-slide");
      const testimonialDots = document.querySelectorAll(
        ".testimonial-dots .dot"
      );
      const prevArrow = document.querySelector(".testimonial-nav .prev");
      const nextArrow = document.querySelector(".testimonial-nav .next");
      let currentTestimonialSlide = 0;

      function showTestimonialSlide(index) {
        testimonialSlides.forEach((slide, i) => {
          slide.classList.toggle("active", i === index);
        });
        testimonialDots.forEach((dot, i) => {
          dot.classList.toggle("active", i === index);
        });
      }

      showTestimonialSlide(currentTestimonialSlide);

      prevArrow.addEventListener("click", () => {
        currentTestimonialSlide =
          (currentTestimonialSlide - 1 + testimonialSlides.length) %
          testimonialSlides.length;
        showTestimonialSlide(currentTestimonialSlide);
      });

      nextArrow.addEventListener("click", () => {
        currentTestimonialSlide =
          (currentTestimonialSlide + 1) % testimonialSlides.length;
        showTestimonialSlide(currentTestimonialSlide);
      });

      testimonialDots.forEach((dot) => {
        dot.addEventListener("click", () => {
          currentTestimonialSlide = parseInt(dot.getAttribute("data-slide"));
          showTestimonialSlide(currentTestimonialSlide);
        });
      });

      setInterval(() => {
        currentTestimonialSlide =
          (currentTestimonialSlide + 1) % testimonialSlides.length;
        showTestimonialSlide(currentTestimonialSlide);
      }, 5000);

      // FAQ Toggle Functionality
      const faqItems = document.querySelectorAll(".faq-item");
      faqItems.forEach((item) => {
        const question = item.querySelector(".faq-question");
        const answer = item.querySelector(".faq-answer");
        const icon = question.querySelector("i");
        question.addEventListener("click", () => {
          answer.classList.toggle("active");
          icon.classList.toggle("active");
        });
      });
    </script>
  </body>
  <script>
    const vehicleContainer = document.querySelector(".vehicle-container");
    const prevButton = document.querySelector(".explore-nav .prev");
    const nextButton = document.querySelector(".explore-nav .next");

    prevButton.addEventListener("click", () => {
      vehicleContainer.scrollBy({
        left: -300,
        behavior: "smooth",
      });
    });

    nextButton.addEventListener("click", () => {
      vehicleContainer.scrollBy({
        left: 300,
        behavior: "smooth",
      });
    });
  </script>
</html>

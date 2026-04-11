# Moodle

<p align="center"><a href="https://moodle.org" target="_blank" title="Moodle Website">
  <img src="https://raw.githubusercontent.com/moodle/moodle/main/.github/moodlelogo.svg" alt="The Moodle Logo">
</a></p>

[Moodle][1] is the World's Open Source Learning Platform, widely used around the world by countless universities, schools, companies, and all manner of organisations and individuals.

Moodle is designed to allow educators, administrators and learners to create personalised learning environments with a single robust, secure and integrated system.

## Documentation

- Read our [User documentation][3]
- Discover our [developer documentation][5]
- Take a look at our [demo site][4]

## Community

[moodle.org][1] is the central hub for the Moodle Community, with spaces for educators, administrators and developers to meet and work together.

You may also be interested in:

- attending a [Moodle Moot][6]
- our regular series of [developer meetings][7]
- the [Moodle User Association][8]

## Installation and hosting

Moodle is Free, and Open Source software. You can easily [download Moodle][9] and run it on your own web server, however you may prefer to work with one of our experienced [Moodle Partners][10].

Moodle also offers hosting through both [MoodleCloud][11], and our [partner network][10].

## License

Moodle is provided freely as open source software, under version 3 of the GNU General Public License. For more information on our license see

[1]: https://moodle.org
[2]: https://moodle.com
[3]: https://docs.moodle.org/
[4]: https://sandbox.moodledemo.net/
[5]: https://moodledev.io
[6]: https://moodle.com/events/mootglobal/
[7]: https://moodledev.io/general/community/meetings
[8]: https://moodleassociation.org/
[9]: https://download.moodle.org
[10]: https://moodle.com/partners
[11]: https://moodle.com/cloud
[12]: https://moodledev.io/general/license


# Moodle-Based Student Consistency Tracking System

## 📌 Overview

This project is a custom Moodle plugin designed to track and analyze student engagement in an e-learning environment. It calculates a daily consistency score based on user activity and enhances quiz integrity through monitoring mechanisms.

## 🚀 Features

* 📊 Real-time activity tracking (notes, videos, assignments, quizzes)
* 📈 Daily consistency score calculation
* 📉 Interactive charts (line & pie) for performance visualization
* 🧑‍💻 Face detection during quizzes
* ⚠️ Tab-switch monitoring with warnings and auto-submit
* 🏆 Leaderboard system for ranking users
* 🔔 Live notifications using toast messages
* 🎨 Customized Moodle UI (login, dashboard)

## 🛠️ Tech Stack

* **Backend:** PHP (Moodle Plugin Development)
* **Frontend:** JavaScript
* **Database:** Moodle Database (MySQL)
* **Other:** Chart libraries, browser APIs (camera, tab tracking)

## ⚙️ Installation

1. Clone the repository:

   ```bash
   git clone https://github.com/yourusername/moodle-consistency-plugin.git
   ```
2. Place the plugin in:

   ```
   moodle/local/consistencyscore
   ```
3. Visit Moodle admin panel → Install plugin
4. Configure settings as required

## 📊 How It Works

* Tracks login/logout, time spent, submissions
* Stores logs in database
* Calculates daily engagement score
* Displays results via charts and leaderboard

## 🎯 Key Learning Outcomes

* Moodle plugin architecture
* Real-time tracking systems
* Data visualization techniques
* Browser-based monitoring (face detection, tab tracking)

## 🔮 Future Improvements

* AI-based cheating detection
* Advanced analytics dashboard
* Mobile compatibility

## 👨‍💻 Author

Sushan Luitel

# LO07 Project Checklist

Below is a clean, copy-paste friendly checklist of the hard requirements, soft constraints, and Project 1 specifics. Check off each item as you complete it.

---

## Part 1: General Rules and Evaluation Criteria

- [ ] Workload: Binome, 70 hours per person (140 hours total). Project must show real complexity.
- [ ] Evaluation: 15-minute demo + 10-minute Q&A.
- [ ] Jury: course instructors.
- [ ] Four key evaluation points:
  - [ ] Architecture: clearly show front-end, back-end, database interactions.
  - [ ] Scenarios: demo with a full story flow (e.g., register -> play a match -> view results).
  - [ ] Originality: justify any library use (e.g., Bootstrap, Chart.js). No plagiarism.
  - [ ] Teamwork balance: prove workload is split evenly.

---

## Part 2: Technical Requirements

- [ ] Front-end: HTML present.
- [ ] Front-end: CSS stylesheet present.
- [ ] Front-end: JavaScript interactivity present (not static only).
- [ ] Data: a clearly defined database schema.
- [ ] Data: persistence (data survives server restarts).
- [ ] Back-end: PHP, JavaScript (Node.js), or another server language.
- [ ] Back-end: handles business logic (Service).
- [ ] Architecture choice explained and justified during defense.

---

## Part 3: Project 1 Functional Requirements (Competition Tracking)

### Core Business Objects

- [ ] Club: user can create a club.
  - [ ] Must specify the game/sport (e.g., chess, tennis).
- [ ] Member: user can create members.
- [ ] Assign member to a club.

### Core Interactions

- [ ] Save match results: two opponents + outcome.
- [ ] Store results in database.
- [ ] Visualize history: list of match records.
- [ ] Filters required (e.g., filter by player name).

### Core Algorithm and Visualization

- [ ] Ranking system implemented (Elo or Glicko2).
- [ ] Rankings auto-updated after each match.
- [ ] Visualize evolution: show score changes over time (line chart).

---

## Part 4: Bonus and Advice

- [ ] Responsive design for desktop, tablet, mobile (appreciated).
- [ ] Optional: auto tournament organization.
- [ ] Optional: match type (friendly vs official).
- [ ] Code organization: MVC strongly recommended; avoid SQL in HTML.

---

## Final 6-Point Checklist

- [ ] Database: supports multiple clubs, members, match history.
- [ ] Algorithm: Elo function in back-end (PHP/JS).
- [ ] Input: form to submit match results, saved to DB.
- [ ] Query: history page with search filter.
- [ ] Chart: player rating evolution (Chart.js or similar).
- [ ] Mobile: layout stays clean on small screens.

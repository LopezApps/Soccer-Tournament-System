# Soccer-Tournament-System
A small and configurable Soccer Tournament System that you can use to track your local league, cup or international tournament. Constructed on PHP 8, HTML5, Javascript and AJAX.
Still a work in progess, so expect many changes in the short term.

This first version allow you to create a tournament of 32 teams, akin to the FIFA World Cup. There are two stages: the group stage, followed by the knockout stage. In the group stage, teams compete within eight groups of four teams each; each group plays a round-robin tournament in which each team is scheduled for three matches against other teams in the same group. This means that a total of six matches are played within a group. The top two teams from each group advance to the knockout stage.

Points are used to rank the teams within a group: 

- 3 points are awarded for a win
- 1 for a draw
- 0 for a loss

The ranking among these teams is determined by the following rules:

- Greatest combined goal difference in all group matches
- Greatest combined number of goals scored in all group matches

If more than one team remain level after applying the above criteria, their ranking will be determined as follows:

- Greatest number of points in head-to-head matches among those teams
- Greatest goal difference in head-to-head matches among those teams
- Greatest number of goals scored in head-to-head matches among those teams
- Fair play points, defined by the number of yellow and red cards received in the group stage:
  - Yellow card: minus 1 point
  - Indirect red card (as a result of a second yellow card): minus 3 points
  - Direct red card: minus 4 points
  - Yellow card and direct red card: minus 5 points
- If any of the teams above remain level after applying the above criteria, their ranking will be determined by the drawing of lots

The knockout stage is a single-elimination tournament in which teams play each other in one-off matches, with extra time and penalty shootouts used to decide the winner if necessary. It begins with the round of 16 (or the second round) in which the winner of each group plays against the runner-up of another group.

This is followed by the quarter-finals, the semi-finals, the third-place match (contested by the losing semi-finalists), and the final, where the winner gets the title of *Champion*.

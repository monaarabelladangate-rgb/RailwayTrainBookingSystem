using System;
using System.Collections;
using System.Collections.Generic;
using System.Drawing;
using System.Net;
using System.Web.Script.Serialization;
using System.Windows.Forms;

namespace TrainTicketBookingApp
{
    public class MainForm : Form
    {
        private string apiUrl = "http://localhost:8000/api.php";
        private JavaScriptSerializer json = new JavaScriptSerializer();

        private DataGridView dgvTrains = new DataGridView();
        private DataGridView dgvTickets = new DataGridView();

        private ComboBox cboTrain = new ComboBox();
        private ComboBox cboBooking = new ComboBox();
        private ComboBox cboPayment = new ComboBox();
        private TextBox txtPassenger = new TextBox();
        private TextBox txtContact = new TextBox();
        private DateTimePicker dtTravel = new DateTimePicker();
        private NumericUpDown numSeats = new NumericUpDown();

        private Button btnRefresh = new Button();
        private Button btnCreate = new Button();
        private Button btnConfirmPaid = new Button();
        private Button btnCancel = new Button();

        private Label lblStatus = new Label();
        private Label lblTrainCount = new Label();
        private Label lblTicketCount = new Label();

        private Color ink = Color.FromArgb(8, 13, 25);
        private Color board = Color.FromArgb(17, 24, 39);
        private Color gold = Color.FromArgb(250, 204, 21);
        private Color green = Color.FromArgb(22, 101, 52);
        private Color soft = Color.FromArgb(250, 250, 245);

        public MainForm()
        {
            Text = "Train Ticket Booking System";
            StartPosition = FormStartPosition.CenterScreen;
            Size = new Size(1240, 760);
            MinimumSize = new Size(1130, 690);
            Font = new Font("Segoe UI", 10);
            BackColor = ink;

            BuildInterface();

            Load += delegate { RefreshAll(); };
        }

        private void BuildInterface()
        {
            Panel side = new Panel();
            side.Location = new Point(0, 0);
            side.Size = new Size(235, 760);
            side.BackColor = Color.FromArgb(3, 7, 18);
            Controls.Add(side);

            Label logo = new Label();
            logo.Text = "RAIL\nPASS";
            logo.Font = new Font("Segoe UI", 24, FontStyle.Bold);
            logo.ForeColor = gold;
            logo.Location = new Point(24, 28);
            logo.Size = new Size(180, 90);
            side.Controls.Add(logo);

            Label sideText = new Label();
            sideText.Text = "Ticket Counter Console\n\nRoutes • Seats • Tickets\nAPI linked to PHP Railway Management";
            sideText.ForeColor = Color.FromArgb(209, 213, 219);
            sideText.Location = new Point(25, 130);
            sideText.Size = new Size(180, 120);
            side.Controls.Add(sideText);

            btnRefresh.Text = "SYNC FROM API";
            btnRefresh.Location = new Point(25, 275);
            btnRefresh.Size = new Size(180, 42);
            StyleButton(btnRefresh, gold, ink);
            btnRefresh.Click += delegate { RefreshAll(); };
            side.Controls.Add(btnRefresh);

            Label apiBox = new Label();
            apiBox.Text = "API TARGET\nlocalhost:8000/api.php";
            apiBox.ForeColor = Color.FromArgb(187, 247, 208);
            apiBox.BackColor = Color.FromArgb(20, 83, 45);
            apiBox.Location = new Point(25, 345);
            apiBox.Size = new Size(180, 70);
            apiBox.TextAlign = ContentAlignment.MiddleCenter;
            apiBox.Font = new Font("Segoe UI", 9, FontStyle.Bold);
            side.Controls.Add(apiBox);

            Panel topBoard = new Panel();
            topBoard.Location = new Point(255, 20);
            topBoard.Size = new Size(945, 105);
            topBoard.BackColor = board;
            Controls.Add(topBoard);

            Label topTitle = new Label();
            topTitle.Text = "TRAIN TICKET BOOKING TERMINAL";
            topTitle.Font = new Font("Consolas", 24, FontStyle.Bold);
            topTitle.ForeColor = gold;
            topTitle.Location = new Point(24, 18);
            topTitle.Size = new Size(700, 38);
            topBoard.Controls.Add(topTitle);

            Label subtitle = new Label();
            subtitle.Text = "A different layout: ticket counter console + route board + manifest monitor";
            subtitle.Font = new Font("Segoe UI", 10, FontStyle.Bold);
            subtitle.ForeColor = Color.FromArgb(229, 231, 235);
            subtitle.Location = new Point(27, 62);
            subtitle.Size = new Size(640, 25);
            topBoard.Controls.Add(subtitle);

            lblTrainCount = MakeBoardNumber("TRAINS", "0", 725, 18);
            topBoard.Controls.Add(lblTrainCount);

            lblTicketCount = MakeBoardNumber("TICKETS", "0", 825, 18);
            topBoard.Controls.Add(lblTicketCount);

            Panel routeBoard = MakePanel(new Point(255, 145), new Size(540, 560), "ROUTE BOARD");
            Controls.Add(routeBoard);

            dgvTrains.Location = new Point(18, 54);
            dgvTrains.Size = new Size(504, 430);
            ConfigureGrid(dgvTrains);
            routeBoard.Controls.Add(dgvTrains);

            Label routeHint = new Label();
            routeHint.Text = "Select a train from the booking panel on the right. Train list comes from PHP API.";
            routeHint.Location = new Point(18, 500);
            routeHint.Size = new Size(500, 32);
            routeHint.ForeColor = Color.FromArgb(75, 85, 99);
            routeBoard.Controls.Add(routeHint);

            Panel ticketPanel = MakePanel(new Point(815, 145), new Size(385, 330), "TICKET ISSUING WINDOW");
            Controls.Add(ticketPanel);

            AddLabel(ticketPanel, "Passenger", 18, 55, 120);
            txtPassenger.Location = new Point(135, 52);
            txtPassenger.Size = new Size(220, 28);
            ticketPanel.Controls.Add(txtPassenger);

            AddLabel(ticketPanel, "Contact", 18, 90, 120);
            txtContact.Location = new Point(135, 87);
            txtContact.Size = new Size(220, 28);
            ticketPanel.Controls.Add(txtContact);

            AddLabel(ticketPanel, "Train", 18, 125, 120);
            cboTrain.Location = new Point(135, 122);
            cboTrain.Size = new Size(220, 28);
            cboTrain.DropDownStyle = ComboBoxStyle.DropDownList;
            ticketPanel.Controls.Add(cboTrain);

            AddLabel(ticketPanel, "Travel Date", 18, 160, 120);
            dtTravel.Location = new Point(135, 157);
            dtTravel.Size = new Size(105, 28);
            dtTravel.Format = DateTimePickerFormat.Short;
            ticketPanel.Controls.Add(dtTravel);

            AddLabel(ticketPanel, "Seats", 250, 160, 50);
            numSeats.Location = new Point(305, 157);
            numSeats.Size = new Size(50, 28);
            numSeats.Minimum = 1;
            numSeats.Maximum = 20;
            numSeats.Value = 1;
            ticketPanel.Controls.Add(numSeats);

            AddLabel(ticketPanel, "Booking", 18, 195, 120);
            cboBooking.Location = new Point(135, 192);
            cboBooking.Size = new Size(105, 28);
            cboBooking.DropDownStyle = ComboBoxStyle.DropDownList;
            cboBooking.Items.Add("Pending");
            cboBooking.Items.Add("Confirmed");
            cboBooking.Items.Add("Cancelled");
            cboBooking.SelectedIndex = 0;
            ticketPanel.Controls.Add(cboBooking);

            AddLabel(ticketPanel, "Payment", 250, 195, 70);
            cboPayment.Location = new Point(305, 192);
            cboPayment.Size = new Size(80, 28);
            cboPayment.DropDownStyle = ComboBoxStyle.DropDownList;
            cboPayment.Items.Add("Unpaid");
            cboPayment.Items.Add("Paid");
            cboPayment.Items.Add("Partial");
            cboPayment.SelectedIndex = 0;
            ticketPanel.Controls.Add(cboPayment);

            btnCreate.Text = "PRINT / SAVE TICKET";
            btnCreate.Location = new Point(18, 245);
            btnCreate.Size = new Size(337, 42);
            StyleButton(btnCreate, green, Color.White);
            btnCreate.Click += delegate { CreateTicket(); };
            ticketPanel.Controls.Add(btnCreate);

            Panel manifestPanel = MakePanel(new Point(815, 495), new Size(385, 210), "PASSENGER MANIFEST");
            Controls.Add(manifestPanel);

            btnConfirmPaid.Text = "CONFIRM + PAID";
            btnConfirmPaid.Location = new Point(18, 50);
            btnConfirmPaid.Size = new Size(150, 34);
            StyleButton(btnConfirmPaid, Color.FromArgb(37, 99, 235), Color.White);
            btnConfirmPaid.Click += delegate { UpdateSelectedTicket("Confirmed", "Paid"); };
            manifestPanel.Controls.Add(btnConfirmPaid);

            btnCancel.Text = "CANCEL";
            btnCancel.Location = new Point(178, 50);
            btnCancel.Size = new Size(90, 34);
            StyleButton(btnCancel, Color.FromArgb(185, 28, 28), Color.White);
            btnCancel.Click += delegate { UpdateSelectedTicket("Cancelled", "Unpaid"); };
            manifestPanel.Controls.Add(btnCancel);

            dgvTickets.Location = new Point(18, 95);
            dgvTickets.Size = new Size(350, 95);
            ConfigureGrid(dgvTickets);
            manifestPanel.Controls.Add(dgvTickets);

            lblStatus.Location = new Point(255, 717);
            lblStatus.Size = new Size(945, 28);
            lblStatus.BackColor = gold;
            lblStatus.ForeColor = ink;
            lblStatus.Font = new Font("Segoe UI", 9, FontStyle.Bold);
            lblStatus.TextAlign = ContentAlignment.MiddleLeft;
            lblStatus.Padding = new Padding(12, 0, 0, 0);
            lblStatus.Text = "Ready. Click SYNC FROM API to load train data.";
            Controls.Add(lblStatus);
        }

        private Label MakeBoardNumber(string title, string value, int x, int y)
        {
            Label box = new Label();
            box.Text = title + "\n" + value;
            box.Location = new Point(x, y);
            box.Size = new Size(85, 68);
            box.BackColor = Color.FromArgb(31, 41, 55);
            box.ForeColor = gold;
            box.Font = new Font("Consolas", 10, FontStyle.Bold);
            box.TextAlign = ContentAlignment.MiddleCenter;
            return box;
        }

        private Panel MakePanel(Point location, Size size, string titleText)
        {
            Panel p = new Panel();
            p.Location = location;
            p.Size = size;
            p.BackColor = soft;

            Label title = new Label();
            title.Text = titleText;
            title.Font = new Font("Consolas", 14, FontStyle.Bold);
            title.ForeColor = green;
            title.Location = new Point(18, 16);
            title.Size = new Size(size.Width - 36, 30);
            p.Controls.Add(title);

            return p;
        }

        private void AddLabel(Control parent, string text, int x, int y, int w)
        {
            Label label = new Label();
            label.Text = text;
            label.Location = new Point(x, y);
            label.Size = new Size(w, 25);
            label.ForeColor = board;
            label.Font = new Font("Segoe UI", 9, FontStyle.Bold);
            parent.Controls.Add(label);
        }

        private void StyleButton(Button button, Color back, Color fore)
        {
            button.BackColor = back;
            button.ForeColor = fore;
            button.FlatStyle = FlatStyle.Flat;
            button.FlatAppearance.BorderSize = 0;
            button.Font = new Font("Segoe UI", 9, FontStyle.Bold);
        }

        private void ConfigureGrid(DataGridView dgv)
        {
            dgv.AllowUserToAddRows = false;
            dgv.AllowUserToDeleteRows = false;
            dgv.ReadOnly = true;
            dgv.MultiSelect = false;
            dgv.SelectionMode = DataGridViewSelectionMode.FullRowSelect;
            dgv.RowHeadersVisible = false;
            dgv.AutoSizeColumnsMode = DataGridViewAutoSizeColumnsMode.Fill;
            dgv.BackgroundColor = Color.White;
            dgv.BorderStyle = BorderStyle.FixedSingle;
            dgv.EnableHeadersVisualStyles = false;
            dgv.ColumnHeadersDefaultCellStyle.BackColor = board;
            dgv.ColumnHeadersDefaultCellStyle.ForeColor = gold;
            dgv.ColumnHeadersDefaultCellStyle.Font = new Font("Segoe UI", 8, FontStyle.Bold);
            dgv.DefaultCellStyle.SelectionBackColor = green;
            dgv.DefaultCellStyle.SelectionForeColor = Color.White;
            dgv.DefaultCellStyle.Font = new Font("Segoe UI", 8);
        }

        private Dictionary<string, object> GetApi(string action)
        {
            using (WebClient client = new WebClient())
            {
                string response = client.DownloadString(apiUrl + "?action=" + action);
                return json.Deserialize<Dictionary<string, object>>(response);
            }
        }

        private string PostApi(Dictionary<string, string> data)
        {
            using (WebClient client = new WebClient())
            {
                client.Headers[HttpRequestHeader.ContentType] = "application/x-www-form-urlencoded";

                List<string> items = new List<string>();

                foreach (KeyValuePair<string, string> item in data)
                {
                    items.Add(Uri.EscapeDataString(item.Key) + "=" + Uri.EscapeDataString(item.Value));
                }

                string body = string.Join("&", items.ToArray());
                return client.UploadString(apiUrl, "POST", body);
            }
        }

        private void RefreshAll()
        {
            try
            {
                LoadTrains();
                LoadTickets();
                lblStatus.Text = "Connected successfully to " + apiUrl;
            }
            catch (Exception ex)
            {
                lblStatus.Text = "Connection failed. Start PHP first: php -S localhost:8000";
                MessageBox.Show("Cannot connect to PHP API.\n\nRun PHP first inside php-api folder:\nphp -S localhost:8000\n\nDetails:\n" + ex.Message);
            }
        }

        private void LoadTrains()
        {
            Dictionary<string, object> result = GetApi("list_trains");
            ArrayList rows = result["trains"] as ArrayList;

            dgvTrains.Columns.Clear();
            dgvTrains.Rows.Clear();

            dgvTrains.Columns.Add("id", "ID");
            dgvTrains.Columns.Add("code", "Code");
            dgvTrains.Columns.Add("name", "Train");
            dgvTrains.Columns.Add("route", "Route");
            dgvTrains.Columns.Add("fare", "Fare");
            dgvTrains.Columns.Add("seats", "Seats");
            dgvTrains.Columns.Add("status", "Status");

            cboTrain.Items.Clear();

            if (rows == null) return;

            foreach (object obj in rows)
            {
                Dictionary<string, object> t = obj as Dictionary<string, object>;

                if (t == null) continue;

                string route = t["origin_station"] + " to " + t["destination_station"];
                dgvTrains.Rows.Add(t["id"], t["train_code"], t["train_name"], route, t["fare"], t["available_seats"], t["status"]);
                cboTrain.Items.Add(new ComboItem(t["train_code"] + " - " + t["train_name"] + " | PHP " + t["fare"], t["id"].ToString()));
            }

            lblTrainCount.Text = "TRAINS\n" + dgvTrains.Rows.Count.ToString();

            if (cboTrain.Items.Count > 0)
            {
                cboTrain.SelectedIndex = 0;
            }
        }

        private void LoadTickets()
        {
            Dictionary<string, object> result = GetApi("list_tickets");
            ArrayList rows = result["tickets"] as ArrayList;

            dgvTickets.Columns.Clear();
            dgvTickets.Rows.Clear();

            dgvTickets.Columns.Add("id", "ID");
            dgvTickets.Columns.Add("passenger", "Passenger");
            dgvTickets.Columns.Add("train", "Train");
            dgvTickets.Columns.Add("status", "Status");

            if (rows == null) return;

            foreach (object obj in rows)
            {
                Dictionary<string, object> b = obj as Dictionary<string, object>;

                if (b == null) continue;

                dgvTickets.Rows.Add(b["id"], b["passenger_name"], b["train_code"], b["booking_status"] + "/" + b["payment_status"]);
            }

            lblTicketCount.Text = "TICKETS\n" + dgvTickets.Rows.Count.ToString();
        }

        private void CreateTicket()
        {
            if (cboTrain.SelectedItem == null)
            {
                MessageBox.Show("Please select a train.");
                return;
            }

            if (txtPassenger.Text.Trim() == "")
            {
                MessageBox.Show("Please enter passenger name.");
                return;
            }

            ComboItem train = cboTrain.SelectedItem as ComboItem;

            try
            {
                string response = PostApi(new Dictionary<string, string>
                {
                    {"action", "create_ticket"},
                    {"passenger_name", txtPassenger.Text.Trim()},
                    {"contact", txtContact.Text.Trim()},
                    {"train_id", train.Value},
                    {"travel_date", dtTravel.Value.ToString("yyyy-MM-dd")},
                    {"seat_count", numSeats.Value.ToString()},
                    {"booking_status", cboBooking.Text},
                    {"payment_status", cboPayment.Text}
                });

                Dictionary<string, object> result = json.Deserialize<Dictionary<string, object>>(response);
                MessageBox.Show(result.ContainsKey("message") ? result["message"].ToString() : "Ticket created.");
                RefreshAll();
            }
            catch (Exception ex)
            {
                MessageBox.Show("Create ticket failed:\n" + ex.Message);
            }
        }

        private void UpdateSelectedTicket(string bookingStatus, string paymentStatus)
        {
            if (dgvTickets.SelectedRows.Count == 0)
            {
                MessageBox.Show("Select a ticket first.");
                return;
            }

            string id = dgvTickets.SelectedRows[0].Cells[0].Value.ToString();

            try
            {
                string response = PostApi(new Dictionary<string, string>
                {
                    {"action", "update_ticket_status"},
                    {"id", id},
                    {"booking_status", bookingStatus},
                    {"payment_status", paymentStatus}
                });

                Dictionary<string, object> result = json.Deserialize<Dictionary<string, object>>(response);
                MessageBox.Show(result.ContainsKey("message") ? result["message"].ToString() : "Ticket updated.");
                RefreshAll();
            }
            catch (Exception ex)
            {
                MessageBox.Show("Update ticket failed:\n" + ex.Message);
            }
        }

        private class ComboItem
        {
            public string Text;
            public string Value;

            public ComboItem(string text, string value)
            {
                Text = text;
                Value = value;
            }

            public override string ToString()
            {
                return Text;
            }
        }
    }
}

package com.my.newproject13;

import android.animation.*;
import android.app.*;
import android.app.Activity;
import android.app.DialogFragment;
import android.app.Fragment;
import android.app.FragmentManager;
import android.content.*;
import android.content.Intent;
import android.content.res.*;
import android.graphics.*;
import android.graphics.drawable.*;
import android.media.*;
import android.net.*;
import android.net.Uri;
import android.os.*;
import android.text.*;
import android.text.style.*;
import android.util.*;
import android.view.*;
import android.view.View;
import android.view.View.*;
import android.view.animation.*;
import android.webkit.*;
import android.webkit.WebSettings;
import android.webkit.WebView;
import android.widget.*;
import android.widget.Button;
import android.widget.ImageView;
import android.widget.LinearLayout;
import android.widget.TextView;
import java.io.*;
import java.text.*;
import java.util.*;
import java.util.Timer;
import java.util.TimerTask;
import java.util.regex.*;
import org.json.*;

public class MainActivity extends Activity {
	
	private Timer _timer = new Timer();
	
	private WebView webview1;
	private LinearLayout linear1;
	private LinearLayout linear2;
	private ImageView imageview1;
	private TextView textview1;
	private LinearLayout linear3;
	private ImageView imageview2;
	private Button button1;
	
	private TimerTask t;
	private Intent i = new Intent();
	private RequestNetwork net;
	private RequestNetwork.RequestListener _net_request_listener;
	
	@Override
	protected void onCreate(Bundle _savedInstanceState) {
		super.onCreate(_savedInstanceState);
		setContentView(R.layout.main);
		initialize(_savedInstanceState);
		initializeLogic();
	}
	
	private void initialize(Bundle _savedInstanceState) {
		webview1 = findViewById(R.id.webview1);
		webview1.getSettings().setJavaScriptEnabled(true);
		webview1.getSettings().setSupportZoom(true);
		linear1 = findViewById(R.id.linear1);
		linear2 = findViewById(R.id.linear2);
		imageview1 = findViewById(R.id.imageview1);
		textview1 = findViewById(R.id.textview1);
		linear3 = findViewById(R.id.linear3);
		imageview2 = findViewById(R.id.imageview2);
		button1 = findViewById(R.id.button1);
		net = new RequestNetwork(this);
		
		webview1.setWebViewClient(new WebViewClient() {
			@Override
			public void onPageStarted(WebView _param1, String _param2, Bitmap _param3) {
				final String _url = _param2;
				
				super.onPageStarted(_param1, _param2, _param3);
			}
			
			@Override
			public void onPageFinished(WebView _param1, String _param2) {
				final String _url = _param2;
				
				super.onPageFinished(_param1, _param2);
			}
		});
		
		button1.setOnClickListener(new View.OnClickListener() {
			@Override
			public void onClick(View _view) {
				webview1.loadUrl("https://purple-dinosaur-966256.hostingersite.com/");
				WebSettings webSettings = webview1.getSettings();
				webSettings.setJavaScriptEnabled(true);
				webSettings.setGeolocationEnabled(true);
				
				// Optional: Set a custom WebChromeClient to handle geolocation permission requests
				webview1.setWebChromeClient(new WebChromeClient() {
					    @Override
					    public void onGeolocationPermissionsShowPrompt(String origin, GeolocationPermissions.Callback callback) {
						        // Grant permission automatically (you might want to handle this better)
						        callback.invoke(origin, true, false);
						    }
				});
				
				net.startRequestNetwork(RequestNetworkController.GET, "https://purple-dinosaur-966256.hostingersite.com/", "A", _net_request_listener);
			}
		});
		
		_net_request_listener = new RequestNetwork.RequestListener() {
			@Override
			public void onResponse(String _param1, String _param2, HashMap<String, Object> _param3) {
				final String _tag = _param1;
				final String _response = _param2;
				final HashMap<String, Object> _responseHeaders = _param3;
				t = new TimerTask() {
					@Override
					public void run() {
						runOnUiThread(new Runnable() {
							@Override
							public void run() {
								webview1.setVisibility(View.VISIBLE);
								linear1.setVisibility(View.GONE);
							}
						});
					}
				};
				_timer.schedule(t, (int)(10));
			}
			
			@Override
			public void onErrorResponse(String _param1, String _param2) {
				final String _tag = _param1;
				final String _message = _param2;
				linear1.setVisibility(View.GONE);
				linear2.setVisibility(View.VISIBLE);
			}
		};
	}
	
	private void initializeLogic() {
		webview1.setVisibility(View.GONE);
		linear1.setVisibility(View.VISIBLE);
		webview1.loadUrl("https://purple-dinosaur-966256.hostingersite.com/");
		WebSettings webSettings = webview1.getSettings();
		webSettings.setJavaScriptEnabled(true);
		webSettings.setGeolocationEnabled(true);
		
		// Optional: Set a custom WebChromeClient to handle geolocation permission requests
		webview1.setWebChromeClient(new WebChromeClient() {
			    @Override
			    public void onGeolocationPermissionsShowPrompt(String origin, GeolocationPermissions.Callback callback) {
				        // Grant permission automatically (you might want to handle this better)
				        callback.invoke(origin, true, false);
				    }
		});
		
		net.startRequestNetwork(RequestNetworkController.GET, "https://purple-dinosaur-966256.hostingersite.com/", "A", _net_request_listener);
	}
	
	@Override
	public void onBackPressed() {
		if (webview1.canGoBack()) {
			webview1.goBack();
		}
		else {
			finish();
		}
	}
	
	@Deprecated
	public void showMessage(String _s) {
		Toast.makeText(getApplicationContext(), _s, Toast.LENGTH_SHORT).show();
	}
	
	@Deprecated
	public int getLocationX(View _v) {
		int _location[] = new int[2];
		_v.getLocationInWindow(_location);
		return _location[0];
	}
	
	@Deprecated
	public int getLocationY(View _v) {
		int _location[] = new int[2];
		_v.getLocationInWindow(_location);
		return _location[1];
	}
	
	@Deprecated
	public int getRandom(int _min, int _max) {
		Random random = new Random();
		return random.nextInt(_max - _min + 1) + _min;
	}
	
	@Deprecated
	public ArrayList<Double> getCheckedItemPositionsToArray(ListView _list) {
		ArrayList<Double> _result = new ArrayList<Double>();
		SparseBooleanArray _arr = _list.getCheckedItemPositions();
		for (int _iIdx = 0; _iIdx < _arr.size(); _iIdx++) {
			if (_arr.valueAt(_iIdx))
			_result.add((double)_arr.keyAt(_iIdx));
		}
		return _result;
	}
	
	@Deprecated
	public float getDip(int _input) {
		return TypedValue.applyDimension(TypedValue.COMPLEX_UNIT_DIP, _input, getResources().getDisplayMetrics());
	}
	
	@Deprecated
	public int getDisplayWidthPixels() {
		return getResources().getDisplayMetrics().widthPixels;
	}
	
	@Deprecated
	public int getDisplayHeightPixels() {
		return getResources().getDisplayMetrics().heightPixels;
	}
}